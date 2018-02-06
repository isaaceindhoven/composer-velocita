<?php

namespace ISAAC\Velocita\Composer\Util;

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Util\RemoteFilesystem;
use ISAAC\Velocita\Composer\Plugins\VelocitaPlugin;

class VelocitaRemoteFilesystem extends RemoteFilesystem
{
    protected const PACKAGES_JSON_FILE = 'packages.json';
    protected const VELOCITA_JSON_FILE = 'packages-velocita.json';

    /** @var VelocitaPlugin */
    protected $plugin;

    public function __construct(VelocitaPlugin $plugin, IOInterface $io, Config $composerConfig = null, array $options = [])
    {
        parent::__construct($io, $composerConfig, $options);

        $this->plugin = $plugin;
    }

    protected function patchURL(string $url): string
    {
        $config = $this->plugin->getConfiguration();
        $endpoints = $this->plugin->getEndpoints();

        // Iterate over endpoints and find a matching prefix
        $mappings = array_merge(
            $endpoints->getRepositories(),
            $endpoints->getDistributionChannels()
        );
        foreach ($mappings as $mapping) {
            // Get normalized prefix
            $prefix = rtrim($mapping->getRemoteURL(), '/') . '/';

            // Does this match?
            if (substr($url, 0, strlen($prefix)) === $prefix) {
                $replacement = sprintf(
                    '%s/%s',
                    rtrim($config->getURL(), '/'),
                    ltrim($mapping->getPath(), '/')
                );
                $url = sprintf(
                    '%s/%s',
                    rtrim($replacement, '/'),
                    substr($url, strlen($prefix))
                );
                break;
            }
        }

        // Map packages.json to packages-velocita.json
        $suffix = '/' . self::PACKAGES_JSON_FILE;
        if (substr($url, -strlen($suffix)) === $suffix) {
            $url = substr($url, 0, -strlen($suffix)) . '/' . self::VELOCITA_JSON_FILE;
        }

        return $url;
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
