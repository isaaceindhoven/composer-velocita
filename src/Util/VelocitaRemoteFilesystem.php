<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Util;

use Composer\Config as ComposerConfig;
use Composer\IO\IOInterface;
use Composer\Util\RemoteFilesystem;
use ISAAC\Velocita\Composer\Config\EndpointMapping;
use ISAAC\Velocita\Composer\Config\Endpoints;
use ISAAC\Velocita\Composer\Config\PluginConfig;

class VelocitaRemoteFilesystem extends RemoteFilesystem
{
    /**
     * @var PluginConfig
     */
    protected $config;
    /**
     * @var Endpoints
     */
    protected $endpoints;
    /**
     * @var IOInterface
     */
    protected $io;

    public function __construct(
        PluginConfig $pluginConfig,
        Endpoints $endpoints,
        IOInterface $io,
        ComposerConfig $config = null,
        array $options = []
    ) {
        parent::__construct($io, $config, $options);

        $this->config = $pluginConfig;
        $this->endpoints = $endpoints;
        $this->io = $io;
    }

    /**
     * @return EndpointMapping[]
     */
    private function getMappings(): array
    {
        return $this->endpoints->getRepositories();
    }

    protected function patchURL(string $url): string
    {
        $patchedUrl = $this->patchURLRoot($url);

        if ($patchedUrl !== $url) {
            $this->io->write(\sprintf('%s(url=%s): %s', __METHOD__, $url, $patchedUrl), true, IOInterface::DEBUG);
        }

        return $patchedUrl;
    }

    private function patchURLRoot(string $url): string
    {
        $patchedUrl = $url;

        foreach ($this->getMappings() as $mapping) {
            $prefix = $mapping->getNormalizedRemoteURL();
            $regex = \sprintf('#^https?:%s(?<path>.+)$#i', \preg_quote($prefix));
            $matches = [];
            if (\preg_match($regex, $patchedUrl, $matches)) {
                $patchedUrl = \sprintf(
                    '%s/%s/%s',
                    \rtrim($this->config->getURL(), '/'),
                    \trim($mapping->getPath(), '/'),
                    \ltrim($matches['path'], '/')
                );
                break;
            }
        }

        return $patchedUrl;
    }

    /**
     * @inheritdoc
     */
    protected function get($originUrl, $fileUrl, $additionalOptions = [], $fileName = null, $progress = true)
    {
        $this->io->write(\sprintf('%s(fileUrl=%s)', __METHOD__, $fileUrl), true, IOInterface::DEBUG);

        $patchedUrl = $this->patchURL($fileUrl);
        return parent::get($originUrl, $patchedUrl, $additionalOptions, $fileName, $progress);
    }
}
