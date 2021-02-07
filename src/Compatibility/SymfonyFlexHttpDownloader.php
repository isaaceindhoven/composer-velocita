<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Util\HttpDownloader;
use ISAAC\Velocita\Composer\UrlMapper;

use function sprintf;

class SymfonyFlexHttpDownloader extends HttpDownloader
{
    /**
     * @var UrlMapper
     */
    protected $urlMapper;
    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @param array<int|string, mixed> $options
     */
    public function __construct(
        UrlMapper $urlMapper,
        IOInterface $io,
        Config $config,
        array $options = [],
        bool $disableTls = false
    ) {
        parent::__construct($io, $config, $options, $disableTls);

        $this->urlMapper = $urlMapper;
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     * @param array<int|string, mixed> $options
     */
    public function get($url, $options = [])
    {
        return parent::get($this->mapUrl($url, __METHOD__), $options);
    }

    /**
     * {@inheritdoc}
     * @param array<int|string, mixed> $options
     */
    public function add($url, $options = [])
    {
        return parent::add($this->mapUrl($url, __METHOD__), $options);
    }

    /**
     * {@inheritdoc}
     * @param array<int|string, mixed> $options
     */
    public function copy($url, $to, $options = [])
    {
        return parent::copy($this->mapUrl($url, __METHOD__), $to, $options);
    }

    protected function mapUrl(string $url, string $methodName): string
    {
        $patchedUrl = $this->urlMapper->applyMappings($url);
        if ($patchedUrl !== $url) {
            $this->io->write(
                sprintf('%s(url=%s): mapped to %s', $methodName, $url, $patchedUrl),
                true,
                IOInterface::DEBUG,
            );
        }
        return $patchedUrl;
    }
}
