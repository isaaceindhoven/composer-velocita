<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreFileDownloadEvent;
use Exception;
use ISAAC\Velocita\Composer\Commands\CommandProvider;
use ISAAC\Velocita\Composer\Config\Endpoints;
use ISAAC\Velocita\Composer\Config\PluginConfig;
use ISAAC\Velocita\Composer\Exceptions\IOException;
use ISAAC\Velocita\Composer\Util\ComposerFactory;
use ISAAC\Velocita\Composer\Util\VelocitaRemoteFilesystem;

class VelocitaPlugin implements PluginInterface, EventSubscriberInterface, Capable
{
    protected const CONFIG_FILE = 'velocita.json';
    protected const MIRRORS_URL = '%s/mirrors.json';

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
    protected $config = null;
    /**
     * @var Endpoints
     */
    protected $endpoints = null;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;

        $this->configPath = \sprintf('%s/%s', ComposerFactory::getComposerHomeDir(), self::CONFIG_FILE);
    }

    public function getCapabilities(): array
    {
        return [
            ComposerCommandProvider::class => CommandProvider::class,
        ];
    }

    public static function getSubscribedEvents(): array
    {
        $method = 'onPreFileDownload';
        $priority = 0;

        // Subscribe to PRE_FILE_DOWNLOAD
        return [
            PluginEvents::PRE_FILE_DOWNLOAD => [
                [$method, $priority],
            ],
        ];
    }

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
        // Don't do anything if we're disabled
        $config = $this->getConfiguration();
        if (!$config->isEnabled()) {
            return;
        }

        $endpoints = $this->getEndpoints();
        $rfs = new VelocitaRemoteFilesystem(
            $config,
            $endpoints,
            $this->io,
            $this->composer->getConfig(),
            $event->getRemoteFilesystem()->getOptions()
        );
        $event->setRemoteFilesystem($rfs);
    }

    protected function loadConfiguration(): PluginConfig
    {
        $data = null;
        if (\is_readable($this->configPath)) {
            $data = \file_get_contents($this->configPath);
        }
        if (\is_string($data)) {
            $data = \json_decode($data, true);
        }
        if (!\is_array($data)) {
            $data = [];
        }
        return PluginConfig::fromArray($data);
    }

    public function getConfiguration(): PluginConfig
    {
        if ($this->config === null) {
            $this->config = $this->loadConfiguration();
        }
        return $this->config;
    }

    public function writeConfiguration(PluginConfig $config): void
    {
        $config->validate();

        // Ensure parent directory exists
        $configDir = \dirname($this->configPath);
        if (!\is_dir($configDir)) {
            \mkdir($configDir, 0777, true);
        }

        $configJSON = \json_encode($config->toArray(), \JSON_PRETTY_PRINT);
        \file_put_contents($this->configPath, $configJSON);
    }

    protected function loadEndpoints(): Endpoints
    {
        $config = $this->getConfiguration();
        $endpointsURL = \sprintf(static::MIRRORS_URL, $config->getURL());
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

    public function getEndpoints(): Endpoints
    {
        if ($this->endpoints === null) {
            $this->endpoints = $this->loadEndpoints();
        }
        return $this->endpoints;
    }
}