<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
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
use RuntimeException;
use UnexpectedValueException;

use function is_array;
use function sprintf;

use const PHP_INT_MAX;

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
    protected $configuration;
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

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        static::$enabled = false;
    }

    private function initialize(): void
    {
        $this->configPath = sprintf('%s/%s', ComposerFactory::getComposerHomeDir(), static::CONFIG_FILE);
        $this->configuration = (new PluginConfigReader())->readOrNew($this->configPath);

        static::$enabled = $this->configuration->isEnabled();
        if (!static::$enabled) {
            return;
        }

        $url = $this->configuration->getURL();
        if ($url === null) {
            throw new LogicException('Velocita enabled but no URL set');
        }
        try {
            $remoteConfig = $this->getRemoteConfig($url);
        } catch (Exception $e) {
            $this->io->writeError(sprintf('Failed to retrieve remote config: %s', $e->getMessage()));
            static::$enabled = false;
            return;
        }

        $this->urlMapper = new UrlMapper($url, $remoteConfig->getMirrors());
        $this->compatibilityDetector = new CompatibilityDetector($this->composer, $this->io, $this->urlMapper);
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public function getCapabilities(): array
    {
        return [
            ComposerCommandProvider::class => CommandProvider::class,
        ];
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        if (!static::$enabled) {
            return [];
        }
        return [
            PluginEvents::PRE_COMMAND_RUN => ['onPreCommandRun', PHP_INT_MAX],
            PackageEvents::POST_PACKAGE_INSTALL => ['onPostPackageInstall', 0],
            PluginEvents::PRE_FILE_DOWNLOAD => ['onPreFileDownload', 0],
        ];
    }

    public function onPreCommandRun(): void
    {
        $this->compatibilityDetector->fixPluginCompatibility();
    }

    public function onPostPackageInstall(PackageEvent $event): void
    {
        $this->compatibilityDetector->onPackageInstall($event);
    }

    public function onPreFileDownload(PreFileDownloadEvent $event): void
    {
        $originalUrl = $event->getProcessedUrl();
        $mappedUrl = $this->urlMapper->applyMappings($originalUrl);
        if ($mappedUrl !== $originalUrl) {
            $this->io->write(
                sprintf('%s(url=%s): mapped to %s', __METHOD__, $originalUrl, $mappedUrl),
                true,
                IOInterface::DEBUG
            );
        }
        $event->setProcessedUrl($mappedUrl);
    }

    public function getConfiguration(): PluginConfig
    {
        return $this->configuration;
    }

    public function writeConfiguration(PluginConfig $config): void
    {
        $writer = new PluginConfigWriter($config);
        $writer->write($this->configPath);
    }

    protected function getRemoteConfig(string $url): RemoteConfig
    {
        $httpDownloader = $this->composer->getLoop()->getHttpDownloader();
        $remoteConfigUrl = sprintf(static::REMOTE_CONFIG_URL, $url);
        $response = $httpDownloader->get($remoteConfigUrl);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                sprintf('Unexpected status code %d for URL %s', $response->getStatusCode(), $remoteConfigUrl)
            );
        }
        $remoteConfigData = $response->decodeJson();
        if (!is_array($remoteConfigData)) {
            throw new UnexpectedValueException('Remote configuration is formatted incorrectly');
        }
        return RemoteConfig::fromArray($remoteConfigData);
    }
}
