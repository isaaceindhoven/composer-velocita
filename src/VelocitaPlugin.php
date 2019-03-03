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
use ISAAC\Velocita\Composer\Compatibility\CompatibilityDetector;
use ISAAC\Velocita\Composer\Composer\OperationAdapter;
use ISAAC\Velocita\Composer\Composer\PackageAdapter;
use ISAAC\Velocita\Composer\Config\PluginConfig;
use ISAAC\Velocita\Composer\Config\PluginConfigReader;
use ISAAC\Velocita\Composer\Config\PluginConfigWriter;
use ISAAC\Velocita\Composer\Config\RemoteConfig;
use ISAAC\Velocita\Composer\Exceptions\IOException;
use ISAAC\Velocita\Composer\Composer\ComposerFactory;
use ISAAC\Velocita\Composer\Util\VelocitaRemoteFilesystem;
use LogicException;

class VelocitaPlugin implements PluginInterface, EventSubscriberInterface, Capable
{
    protected const CONFIG_FILE = 'velocita.json';
    protected const REMOTE_CONFIG_URL = '%s/mirrors.json';

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
    /**
     * @var CompatibilityDetector
     */
    protected $compatibilityDetector;

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
        $mappings = $this->getRemoteConfig()->getMirrors();
        $this->urlMapper = new UrlMapper($url, $mappings);

        $this->compatibilityDetector = new CompatibilityDetector($composer, $io);
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
            InstallerEvents::PRE_DEPENDENCIES_SOLVING  => ['onPreDependenciesSolving', \PHP_INT_MAX],
            InstallerEvents::POST_DEPENDENCIES_SOLVING => ['onPostDependenciesSolving', \PHP_INT_MAX],
            PluginEvents::PRE_FILE_DOWNLOAD            => ['onPreFileDownload', 0],
        ];
    }

    public function onPreDependenciesSolving(InstallerEvent $event): void
    {
        $this->compatibilityDetector->fixCompatibility();
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

    protected function getRemoteConfig(): RemoteConfig
    {
        $remoteConfigUrl = \sprintf(static::REMOTE_CONFIG_URL, $this->config->getURL());
        $remoteConfigJSON = \file_get_contents($remoteConfigUrl);
        if ($remoteConfigJSON === false) {
            throw new IOException('Unable to retrieve remote Velocita configuration');
        }
        $remoteConfigData = \json_decode($remoteConfigJSON, true);
        if (!\is_array($remoteConfigData)) {
            throw new IOException(
                \sprintf('Invalid JSON structure (#%d: %s)', \json_last_error(), \json_last_error_msg())
            );
        }
        return RemoteConfig::fromArray($remoteConfigData);
    }
}
