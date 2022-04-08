<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Config;

use UnexpectedValueException;

use function array_key_exists;
use function rtrim;
use function sprintf;

class MirrorMapping
{
    protected string $url;
    protected string $path;

    /**
     * @param array{url?: string, path?: string} $data
     */
    public static function fromArray(array $data): MirrorMapping
    {
        if (!array_key_exists('url', $data) || !array_key_exists('path', $data)) {
            throw new UnexpectedValueException('Missing `url` or `path` key in mirror mapping');
        }

        $mapping = new MirrorMapping();
        $mapping->url = $data['url'];
        $mapping->path = $data['path'];
        return $mapping;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getNormalizedUrl(): string
    {
        return sprintf('%s/', rtrim($this->url, '/'));
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
