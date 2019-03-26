<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer;

use ISAAC\Velocita\Composer\Config\MirrorMapping;

class UrlMapper
{
    /**
     * @var MirrorMapping[]
     */
    private $mappings;
    /**
     * @var string
     */
    private $rootUrl;

    /**
     * @param MirrorMapping[] $mappings
     */
    public function __construct(string $rootUrl, array $mappings)
    {
        $this->mappings = $mappings;
        $this->rootUrl = $rootUrl;
    }

    public function applyMappings(string $url): string
    {
        $patchedUrl = $this->applyGitHubShortcut($url);

        foreach ($this->mappings as $mapping) {
            $prefix = $mapping->getNormalizedUrl();
            $regex = \sprintf('#^https?:%s(?<path>.+)$#i', \preg_quote($prefix));
            $matches = [];
            if (\preg_match($regex, $patchedUrl, $matches)) {
                $patchedUrl = \sprintf(
                    '%s/%s/%s',
                    \rtrim($this->rootUrl, '/'),
                    \trim($mapping->getPath(), '/'),
                    \ltrim($matches['path'], '/')
                );
                break;
            }
        }

        return $patchedUrl;
    }

    protected function applyGitHubShortcut(string $url): string
    {
        $matches = [];
        if (\preg_match(
            '#^https://api.github.com/repos/(?<package>.+)/zipball/(?<hash>[0-9a-f]+)$#i',
            $url,
            $matches
        )) {
            return \sprintf(
                'https://codeload.github.com/%s/legacy.zip/%s',
                $matches['package'],
                $matches['hash']
            );
        }
        return $url;
    }
}
