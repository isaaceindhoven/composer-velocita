<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer;

use ISAAC\Velocita\Composer\Config\MirrorMapping;

use function ltrim;
use function preg_match;
use function preg_quote;
use function rtrim;
use function sprintf;
use function trim;

class UrlMapper
{
    private const GITHUB_REGEX = '#^https://api.github.com/repos/(?<package>.+)/zipball/(?<hash>[0-9a-f]+)$#i';

    /**
     * @var MirrorMapping[]
     */
    private array $mappings;
    private string $rootUrl;

    /**
     * @param MirrorMapping[] $mappings
     */
    public function __construct(string $rootUrl, array $mappings)
    {
        $this->mappings = $mappings;
        $this->rootUrl = $rootUrl;
    }

    /**
     * @param non-empty-string $url
     * @return non-empty-string
     */
    public function applyMappings(string $url): string
    {
        $patchedUrl = $this->applyGitHubShortcut($url);

        foreach ($this->mappings as $mapping) {
            $prefix = $mapping->getNormalizedUrl();
            $regex = sprintf('#^https?:%s(?<path>.+)$#i', preg_quote($prefix));
            $matches = [];
            if (preg_match($regex, $patchedUrl, $matches) === 1) {
                $patchedUrl = sprintf(
                    '%s/%s/%s',
                    rtrim($this->rootUrl, '/'),
                    trim($mapping->getPath(), '/'),
                    ltrim($matches['path'], '/')
                );
                break;
            }
        }

        return $patchedUrl;
    }

    /**
     * @param non-empty-string $url
     * @return non-empty-string
     */
    protected function applyGitHubShortcut(string $url): string
    {
        $matches = [];
        if (preg_match(self::GITHUB_REGEX, $url, $matches) === 1) {
            $package = $matches['package'];
            $hash = $matches['hash'];
            return sprintf('https://codeload.github.com/%s/legacy.zip/%s', $package, $hash);
        }
        return $url;
    }
}
