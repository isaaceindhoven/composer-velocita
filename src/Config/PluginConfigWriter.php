<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Config;

use function dirname;
use function file_put_contents;
use function is_dir;
use function json_encode;
use function mkdir;

use const JSON_PRETTY_PRINT;

class PluginConfigWriter
{
    /**
     * @var PluginConfig
     */
    private $pluginConfig;

    public function __construct(PluginConfig $pluginConfig)
    {
        $this->pluginConfig = $pluginConfig;
    }

    /**
     * @return array{enabled: bool, url: ?string}
     */
    protected function getPayload(): array
    {
        return [
            'enabled' => $this->pluginConfig->isEnabled(),
            'url'     => $this->pluginConfig->getURL(),
        ];
    }

    public function write(string $path): void
    {
        $this->pluginConfig->validate();

        // Ensure parent directory exists
        $configDir = dirname($path);
        if (!is_dir($configDir)) {
            mkdir($configDir, 0777, true);
        }

        $configJSON = json_encode($this->getPayload(), JSON_PRETTY_PRINT);
        file_put_contents($path, $configJSON);
    }
}
