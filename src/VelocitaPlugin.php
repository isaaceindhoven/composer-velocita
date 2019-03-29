<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvents;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreFileDownloadEvent;
use Exception;
use ISAAC\Velocita\Composer\Commands\CommandProvider;
use ISAAC\Velocita\Composer\Compatibility\CompatibilityDetector;
use ISAAC\Velocita\Composer\Composer\ComposerFactory;
use ISAAC\Velocita\Composer\Config\PluginConfig;
use ISAAC\Velocita\Composer\Config\PluginConfigReader;
use ISAAC\Velocita\Composer\Config\PluginConfigWriter;
use ISAAC\Velocita\Composer\Config\RemoteConfig;
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

        $this->initialize();
    }

    private function initialize(): void
    {
        $this->configPath = \sprintf('%s/%s', ComposerFactory::getComposerHomeDir(), static::CONFIG_FILE);
        $this->config = (new PluginConfigReader())->readOrNew($this->configPath);

        static::$enabled = $this->config->isEnabled();
        if (!static::$enabled) {
            return;
        }

        $url = $this->config->getURL();
        if ($url === null) {
            throw new LogicException('Velocita enabled but no URL set');
        }
        try {
            $remoteConfig = $this->getRemoteConfig($url);
        } catch (Exception $e) {
            $this->io->writeError(\sprintf('Failed to retrieve remote config: %s', $e->getMessage()));
            static::$enabled = false;
            return;
        }

        $this->urlMapper = new UrlMapper($url, $remoteConfig->getMirrors());
        $this->compatibilityDetector = new CompatibilityDetector($this->composer, $this->io, $this->urlMapper);
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
            InstallerEvents::PRE_DEPENDENCIES_SOLVING => ['onPreDependenciesSolving', \PHP_INT_MAX],
            PackageEvents::POST_PACKAGE_INSTALL       => ['onPostPackageInstall', 0],
            PluginEvents::PRE_FILE_DOWNLOAD           => ['onPreFileDownload', 0],
        ];
    }

    public function onPreDependenciesSolving(): void
    {
        $this->compatibilityDetector->fixCompatibility();
    }

    public function onPostPackageInstall(PackageEvent $event): void
    {
        $this->compatibilityDetector->onPackageInstall($event);
    }

    /**
     * @throws Exception
     */
    public function onPreFileDownload(PreFileDownloadEvent $event): void
    {
        /*
         * Handle all exceptions that ::handlePreFileDownloadEvent() might throw at us by being verbose about it.
         *
         * Unfortunately we need to do this; at least in Composer 1.8.4 EventDispatcher ignores exceptions causing its
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
        $currentRfs = $event->getRemoteFilesystem();
        $velocitaRfs = new RemoteFilesystem(
            $this->urlMapper,
            $this->io,
            $this->composer->getConfig(),
            $currentRfs->getOptions(),
            $currentRfs->isTlsDisabled()
        );
        $event->setRemoteFilesystem($velocitaRfs);
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

    protected function getRemoteConfig(string $url): RemoteConfig
    {
        $remoteConfigUrl = \sprintf(static::REMOTE_CONFIG_URL, $url);
        $remoteConfigJSON = \file_get_contents($remoteConfigUrl);
        $remoteConfigData = \json_decode((string) $remoteConfigJSON, true);
        return RemoteConfig::fromArray($remoteConfigData);
    }
}
