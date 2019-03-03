<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Config;

class MirrorMapping
{
    /**
     * @var string
     */
    protected $url;
    /**
     * @var string
     */
    protected $path;

    public static function fromArray(array $data): MirrorMapping
    {
        $mapping = new MirrorMapping();
        $mapping->url = $data['url'] ?? null;
        $mapping->path = $data['path'] ?? null;
        return $mapping;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getNormalizedUrl(): string
    {
        return \sprintf('%s/', \rtrim($this->url, '/'));
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
