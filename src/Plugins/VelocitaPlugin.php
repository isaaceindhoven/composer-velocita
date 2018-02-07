<?php

namespace ISAAC\Velocita\Composer\Plugins;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreFileDownloadEvent;
use ISAAC\Velocita\Composer\Config\Endpoints;
use ISAAC\Velocita\Composer\Config\PluginConfig;
use ISAAC\Velocita\Composer\Exceptions\CommunicationException;
use ISAAC\Velocita\Composer\Util\VelocitaRemoteFilesystem;

class VelocitaPlugin implements PluginInterface, EventSubscriberInterface, Capable
{
    protected const CONFIG_FILE = 'velocita.json';

    /** @var Composer */
    protected $composer;

    /** @var IOInterface */
    protected $io;

    /** @var string */
    protected $configPath;

    /** @var PluginConfig */
    protected $config = null;

    /** @var Endpoints */
    protected $endpoints = null;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;

        $this->configPath = sprintf('%s/.composer/%s', getenv('HOME'), self::CONFIG_FILE);
    }

    public function getCapabilities(): array
    {
        return [
            'Composer\\Plugin\\Capability\\CommandProvider' => 'ISAAC\\Velocita\\Composer\\Commands\\CommandProvider',
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

    protected function loadConfiguration(): PluginConfig
    {
        $data = null;
        if (is_readable($this->configPath)) {
            $data = json_decode(file_get_contents($this->configPath), true);
        }
        if (!is_array($data)) {
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
        $configDir = dirname($this->configPath);
        if (!is_dir($configDir)) {
            mkdir($configDir);
        }

        file_put_contents($this->configPath, json_encode($config->toArray()));
    }

    protected function loadEndpoints(): Endpoints
    {
        $config = $this->getConfiguration();
        $endpointsURL = sprintf('%s/endpoints', $config->getURL());
        $endpointsJSON = file_get_contents($endpointsURL);
        if ($endpointsJSON === false) {
            throw new CommunicationException('Unable to retrieve endpoints configuration from Velocita');
        }
        $endpoints = json_decode($endpointsJSON, true);
        if (!is_array($endpoints)) {
            throw new CommunicationException(
                sprintf('Invalid JSON structure retrieved (#%d: %s)', json_last_error(), json_last_error_msg())
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

    public function onPreFileDownload(PreFileDownloadEvent $event): void
    {
        // Don't do anything if we're disabled
        $config = $this->getConfiguration();
        if (!$config->isEnabled()) {
            return;
        }

        $rfs = new VelocitaRemoteFilesystem(
            $this,
            $this->io,
            $this->composer->getConfig(),
            $event->getRemoteFilesystem()->getOptions()
        );
        $event->setRemoteFilesystem($rfs);
    }
}
