<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Config;

use Exception;

class PluginConfig
{
    /**
     * @var bool
     */
    protected $enabled;
    /**
     * @var string
     */
    protected $url;

    public static function fromArray(array $data): PluginConfig
    {
        $config = new PluginConfig();
        $config->enabled = $data['enabled'] ?? false;
        $config->url     = $data['url']     ?? null;
        return $config;
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'url'     => $this->url,
        ];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getURL(): string
    {
        return $this->url;
    }

    public function setURL(string $url): void
    {
        $this->url = $url;
    }

    public function validate(): void
    {
        // If set, the URL must be valid
        if (($this->url !== null) && !\filter_var($this->url, \FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL was set for this plugin');
        }

        // If enabled, a URL must also be set
        if ($this->enabled && ($this->url === null)) {
            throw new Exception('A URL must be set for this plugin to be enabled');
        }
    }
}
