<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvent;
use Composer\Installer\InstallerEvents;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreFileDownloadEvent;
use Exception;
use ISAAC\Velocita\Composer\Commands\CommandProvider;
use ISAAC\Velocita\Composer\Composer\OperationAdapter;
use ISAAC\Velocita\Composer\Composer\PackageAdapter;
use ISAAC\Velocita\Composer\Config\Endpoints;
use ISAAC\Velocita\Composer\Config\PluginConfig;
use ISAAC\Velocita\Composer\Config\PluginConfigReader;
use ISAAC\Velocita\Composer\Config\PluginConfigWriter;
use ISAAC\Velocita\Composer\Exceptions\IOException;
use ISAAC\Velocita\Composer\Util\ComposerFactory;
use ISAAC\Velocita\Composer\Util\VelocitaRemoteFilesystem;
use LogicException;

class VelocitaPlugin implements PluginInterface, EventSubscriberInterface, Capable
{
    protected const CONFIG_FILE = 'velocita.json';
    protected const MIRRORS_URL = '%s/mirrors.json';

    /**
     * @var bool
     */
    protected static $enabled = true;

    /**
     * @var Composer
     */
    protected $composer;
    /**
     * @var IOInterface
     */
    protected $io;
    /**
     * @var string
     */
    protected $configPath;
    /**
     * @var PluginConfig
     */
    protected $config;
    /**
     * @var UrlMapper
     */
    protected $urlMapper;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;

        $this->configPath = \sprintf('%s/%s', ComposerFactory::getComposerHomeDir(), self::CONFIG_FILE);
        $configReader = new PluginConfigReader();
        $this->config = $configReader->readOrNew($this->configPath);

        static::$enabled = $this->config->isEnabled();
        if (!static::$enabled) {
            return;
        }

        $url = $this->config->getURL();
        if ($url === null) {
            throw new LogicException('Velocita enabled but no URL set');
        }
        $mappings = $this->loadEndpoints()->getMirrors();
        $this->urlMapper = new UrlMapper($url, $mappings);
    }

    public function getCapabilities(): array
    {
        return [
            ComposerCommandProvider::class => CommandProvider::class,
        ];
    }

    public static function getSubscribedEvents(): array
    {
        if (!static::$enabled) {
            return [];
        }
        return [
            InstallerEvents::POST_DEPENDENCIES_SOLVING => ['onPostDependenciesSolving', \PHP_INT_MAX],
            PluginEvents::PRE_FILE_DOWNLOAD            => ['onPreFileDownload', 0],
        ];
    }

    public function onPostDependenciesSolving(InstallerEvent $event): void
    {
        foreach ($event->getOperations() as $operation) {
            if (!$operation instanceof InstallOperation && !$operation instanceof UpdateOperation) {
                continue;
            }

            $operationAdapter = new OperationAdapter($operation);
            $package = $operationAdapter->getPackage();

            $this->patchPackage($package);
        }
    }

    private function patchPackage(Package $package): void
    {
        $packageAdapter = new PackageAdapter($package);
        $primaryUrl = $packageAdapter->getPrimaryUrl();
        if ($primaryUrl === null) {
            return;
        }

        $patchedUrl = $this->urlMapper->applyMappings($primaryUrl);
        if ($patchedUrl === $primaryUrl) {
            return;
        }

        $this->io->write(
            \sprintf('%s(url=%s): %s', __METHOD__, $primaryUrl, $patchedUrl),
            true,
            IOInterface::DEBUG
        );

        /** @var $package Package */
        $package->setDistMirrors([
            [
                'url'       => $patchedUrl,
                'preferred' => true,
            ]
        ]);
    }

    /**
     * @throws Exception
     */
    public function onPreFileDownload(PreFileDownloadEvent $event): void
    {
        /*
         * Handle all exceptions that ::handlePreFileDownloadEvent() might throw at us by being verbose about it.
         *
         * Unfortunately we need to do this; at least in Composer 1.6.3 EventDispatcher ignores exceptions causing its
         * circular invocation detection to trigger as soon as a second event of the same type is dispatched.
         */
        try {
            $this->handlePreFileDownloadEvent($event);
        } catch (Exception $e) {
            $this->io->writeError(
                \sprintf(
                    "<error>Velocita: exception thrown in event handler: %s\n%s</error>",
                    $e->getMessage(),
                    $e->getTraceAsString()
                )
            );
            throw $e;
        }
    }

    protected function handlePreFileDownloadEvent(PreFileDownloadEvent $event): void
    {
        $rfs = new VelocitaRemoteFilesystem(
            $this->urlMapper,
            $this->io,
            $this->composer->getConfig(),
            $event->getRemoteFilesystem()->getOptions()
        );
        $event->setRemoteFilesystem($rfs);
    }

    public function getConfiguration(): PluginConfig
    {
        return $this->config;
    }

    public function writeConfiguration(PluginConfig $config): void
    {
        $writer = new PluginConfigWriter($config);
        $writer->write($this->configPath);
    }

    protected function loadEndpoints(): Endpoints
    {
        $endpointsURL = \sprintf(static::MIRRORS_URL, $this->config->getURL());
        $endpointsJSON = \file_get_contents($endpointsURL);
        if ($endpointsJSON === false) {
            throw new IOException('Unable to retrieve endpoints configuration from Velocita');
        }
        $endpoints = \json_decode($endpointsJSON, true);
        if (!\is_array($endpoints)) {
            throw new IOException(
                \sprintf('Invalid JSON structure retrieved (#%d: %s)', \json_last_error(), \json_last_error_msg())
            );
        }
        return Endpoints::fromArray($endpoints);
    }
}
