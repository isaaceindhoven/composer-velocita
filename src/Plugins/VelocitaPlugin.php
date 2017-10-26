<?php

namespace ISAAC\Velocita\Composer\Plugins;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreFileDownloadEvent;
use ISAAC\Velocita\Composer\Config\Endpoints;
use ISAAC\Velocita\Composer\Config\PluginConfig;
use ISAAC\Velocita\Composer\Util\VelocitaRemoteFilesystem;

class VelocitaPlugin implements PluginInterface, EventSubscriberInterface
{
    protected const CONFIG_FILE = 'velocita.json';

    /** @var Composer */
    protected $composer;

    /** @var IOInterface */
    protected $io;

    /** @var PluginConfig */
    protected $config = null;

    /** @var Endpoints */
    protected $endpoints = null;

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    protected function loadConfiguration(): PluginConfig
    {
        $configPath = sprintf('%s/.composer/%s', getenv('HOME'), self::CONFIG_FILE);
        if (is_readable($configPath)) {
            $data = json_decode(file_get_contents($configPath), true);
        } else {
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

    protected function loadEndpoints(): Endpoints
    {
        $config = $this->getConfiguration();
        $endpointsURL = sprintf('%s/endpoints', $config->getUrl());
        $data = json_decode(file_get_contents($endpointsURL), true);
        return Endpoints::fromArray($data);
    }

    public function getEndpoints(): Endpoints
    {
        if ($this->endpoints === null) {
            $this->endpoints = $this->loadEndpoints();
        }
        return $this->endpoints;
    }

    public static function getSubscribedEvents(): array
    {
        // Subscribe to PRE_FILE_DOWNLOAD
        $method = 'onPreFileDownload';
        $priority = 0;
        return [
            PluginEvents::PRE_FILE_DOWNLOAD => [
                [$method, $priority],
            ],
        ];
    }

    public function onPreFileDownload(PreFileDownloadEvent $event)
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
