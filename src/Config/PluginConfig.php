<?php

namespace ISAAC\Velocita\Composer\Config;

class PluginConfig
{
    /** @var boolean */
    protected $enabled;

    /** @var string */
    protected $url;

    public static function fromArray(array $data): PluginConfig
    {
        $config = new PluginConfig();
        $config->enabled = $data['enabled'] ?? false;
        $config->url = $data['url'] ?? null;
        return $config;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
