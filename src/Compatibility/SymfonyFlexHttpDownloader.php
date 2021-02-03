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
        $patchedUrl = $this->urlMapper->applyMappings($url);
        if ($patchedUrl !== $url) {
            $this->io->write(
                sprintf('%s(url=%s): mapped to %s', __METHOD__, $url, $patchedUrl),
                true,
                IOInterface::DEBUG
            );
        }
        return parent::get($patchedUrl, $options);
    }
}
