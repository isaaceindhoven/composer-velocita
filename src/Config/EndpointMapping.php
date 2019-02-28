<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Config;

class EndpointMapping
{
    /**
     * @var string
     */
    protected $remoteURL;
    /**
     * @var string
     */
    protected $path;

    public static function fromArray(array $data): EndpointMapping
    {
        $mapping = new EndpointMapping();
        $mapping->remoteURL = $data['url'] ?? null;
        $mapping->path = $data['path'] ?? null;
        return $mapping;
    }

    public function getRemoteURL(): string
    {
        return $this->remoteURL;
    }

    public function getNormalizedRemoteURL(): string
    {
        return \sprintf('%s/', \rtrim($this->remoteURL, '/'));
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
