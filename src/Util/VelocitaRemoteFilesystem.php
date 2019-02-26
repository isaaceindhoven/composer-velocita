<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Util;

use Composer\Config as ComposerConfig;
use Composer\IO\IOInterface;
use Composer\Util\RemoteFilesystem;
use ISAAC\Velocita\Composer\Config\EndpointMapping;
use ISAAC\Velocita\Composer\Config\PluginConfig;
use ISAAC\Velocita\Composer\Plugins\VelocitaPlugin;

class VelocitaRemoteFilesystem extends RemoteFilesystem
{
    protected const PACKAGES_JSON_FILE = 'packages.json';
    protected const VELOCITA_JSON_FILE = 'packages-velocita.json';

    /**
     * @var PluginConfig
     */
    protected $config;
    /**
     * @var VelocitaPlugin
     */
    protected $plugin;
    /**
     * @var IOInterface
     */
    protected $io;

    public function __construct(
        VelocitaPlugin $plugin,
        IOInterface $io,
        ComposerConfig $config = null,
        array $options = []
    ) {
        parent::__construct($io, $config, $options);

        $this->plugin = $plugin;
        $this->io = $io;
        $this->config = $this->plugin->getConfiguration();
    }

    private function findMatchingMapping(string $url): ?EndpointMapping
    {
        $mappings = $this->getMappings();
        foreach ($mappings as $mapping) {
            $prefix = $mapping->getNormalizedRemoteURL();
            if (\substr($url, 0, \strlen($prefix)) === $prefix) {
                return $mapping;
            }
        }

        return null;
    }

    /**
     * @return EndpointMapping[]
     */
    private function getMappings(): array
    {
        $endpoints = $this->plugin->getEndpoints();
        return \array_merge(
            $endpoints->getRepositories(),
            $endpoints->getDistributionChannels()
        );
    }

    protected function patchURL(string $url): string
    {
        $patchedUrl = $url;

        $patchedUrl = $this->patchURLRoot($patchedUrl);
        $patchedUrl = $this->patchURLFile($patchedUrl);

        if ($patchedUrl !== $url) {
            $this->io->write(\sprintf('%s(url=%s): %s', __METHOD__, $url, $patchedUrl), true, IOInterface::DEBUG);
        }

        return $patchedUrl;
    }

    private function patchURLRoot(string $url): string
    {
        $patchedUrl = $url;

        $matchedMapping = $this->findMatchingMapping($patchedUrl);
        if ($matchedMapping !== null) {
            $replacement = \sprintf(
                '%s/%s',
                \rtrim($this->config->getURL(), '/'),
                \ltrim($matchedMapping->getPath(), '/')
            );
            $patchedUrl = \sprintf(
                '%s/%s',
                \rtrim($replacement, '/'),
                \substr($url, \strlen($matchedMapping->getNormalizedRemoteURL()))
            );
        }

        return $patchedUrl;
    }

    private function patchURLFile(string $url): string
    {
        $patchedUrl = $url;

        $suffix = \sprintf('/%s', self::PACKAGES_JSON_FILE);
        if (\substr($patchedUrl, -\strlen($suffix)) === $suffix) {
            $patchedUrl = \sprintf(
                '%s/%s',
                \substr($patchedUrl, 0, -\strlen($suffix)),
                static::VELOCITA_JSON_FILE
            );
        }

        return $patchedUrl;
    }

    /**
     * @inheritdoc
     */
    public function copy($originUrl, $fileUrl, $fileName, $progress = true, $options = []): bool
    {
        $this->io->write(\sprintf('%s(fileUrl=%s)', __METHOD__, $fileUrl), true, IOInterface::DEBUG);

        $patchedUrl = $this->patchURL($fileUrl);
        return parent::copy($originUrl, $patchedUrl, $fileName, $progress, $options);
    }

    /**
     * @inheritdoc
     */
    public function getContents($originUrl, $fileUrl, $progress = true, $options = [])
    {
        $this->io->write(\sprintf('%s(fileUrl=%s)', __METHOD__, $fileUrl), true, IOInterface::DEBUG);

        $patchedUrl = $this->patchURL($fileUrl);
        return parent::getContents($originUrl, $patchedUrl, $progress, $options);
    }
}
