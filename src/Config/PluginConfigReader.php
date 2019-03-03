<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Config;

use ISAAC\Velocita\Composer\Exceptions\IOException;

class PluginConfigReader
{
    protected function getPluginConfigForPayload(array $payload): PluginConfig
    {
        $config = new PluginConfig();
        if (\array_key_exists('enabled', $payload)) {
            $config->setEnabled($payload['enabled']);
        }
        if (\array_key_exists('url', $payload)) {
            $config->setURL($payload['url']);
        }
        return $config;
    }

    /**
     * @throws IOException
     */
    public function read(string $path): PluginConfig
    {
        if (!\is_readable($path)) {
            throw new IOException('Unable to read configuration');
        }

        $data = \file_get_contents($path);
        if ($data === false) {
            throw new IOException('Failed to read configuration');
        }

        $data = \json_decode($data, true);
        if ($data === null) {
            throw new IOException('Could not decode configuration JSON');
        }

        return $this->getPluginConfigForPayload($data);
    }

    public function readOrNew(string $path): PluginConfig
    {
        try {
            return $this->read($path);
        } catch (IOException $e) {
            return new PluginConfig();
        }
    }
}
