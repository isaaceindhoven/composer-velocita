<?php

namespace ISAAC\Velocita\Composer\Util;

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Util\RemoteFilesystem;
use ISAAC\Velocita\Composer\Config\EndpointMapping;
use ISAAC\Velocita\Composer\Plugins\VelocitaPluginInterface;

class VelocitaRemoteFilesystem extends RemoteFilesystem
{
    protected const PACKAGES_JSON_FILE = 'packages.json';
    protected const VELOCITA_JSON_FILE = 'packages-velocita.json';

    /** @var VelocitaPluginInterface */
    protected $plugin;

    /** @var IOInterface */
    protected $io;

    public function __construct(VelocitaPluginInterface $plugin, IOInterface $io, Config $config = null,
                                array $options = [])
    {
        parent::__construct($io, $config, $options);

        $this->plugin = $plugin;
        $this->io = $io;
    }

    protected function patchURL(string $url): string
    {
        $config = $this->plugin->getConfiguration();
        $endpoints = $this->plugin->getEndpoints();
        $patchedUrl = $url;

        /** @var EndpointMapping[] $mappings */
        $mappings = array_merge(
            $endpoints->getRepositories(),
            $endpoints->getDistributionChannels()
        );

        // Iterate over endpoints and find a matching prefix
        foreach ($mappings as $mapping) {
            // Normalize prefix
            $prefix = rtrim($mapping->getRemoteURL(), '/') . '/';

            // Replace prefix if it matches with this endpoint
            if (substr($url, 0, strlen($prefix)) === $prefix) {
                $replacement = sprintf(
                    '%s/%s',
                    rtrim($config->getURL(), '/'),
                    ltrim($mapping->getPath(), '/')
                );
                $patchedUrl = sprintf(
                    '%s/%s',
                    rtrim($replacement, '/'),
                    substr($url, strlen($prefix))
                );
                break;
            }
        }

        // Map packages.json to packages-velocita.json
        $suffix = '/' . self::PACKAGES_JSON_FILE;
        if (substr($patchedUrl, -strlen($suffix)) === $suffix) {
            $patchedUrl = substr($patchedUrl, 0, -strlen($suffix)) . '/' . self::VELOCITA_JSON_FILE;
        }

        return $patchedUrl;
    }

    public function copy($originUrl, $fileUrl, $fileName, $progress = true, $options = [])
    {
        $patchedUrl = $this->patchURL($fileUrl);
        parent::copy($originUrl, $patchedUrl, $fileName, $progress, $options);
    }

    public function getContents($originUrl, $fileUrl, $progress = true, $options = [])
    {
        $patchedUrl = $this->patchURL($fileUrl);
        return parent::getContents($originUrl, $patchedUrl, $progress, $options);
    }
}
