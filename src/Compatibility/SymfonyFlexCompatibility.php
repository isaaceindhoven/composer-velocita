<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

use Closure;
use Composer\Composer;
use Composer\IO\IOInterface;
use ISAAC\Velocita\Composer\UrlMapper;
use Symfony\Flex\Downloader;
use Symfony\Flex\Flex;

/**
 * Symfony Flex and Velocita work great together, but the parallel dist file prefetcher in Flex is implemented as a new
 * RemoteFilesystem that completely bypasses any RFS already in place. Velocita fixes compatibility with Flex by
 * replacing their RemoteFilesystem with our own extension, which then redirects URLs to the Velocita proxy.
 */
class SymfonyFlexCompatibility implements CompatibilityFix
{
    /**
     * @var Composer
     */
    private $composer;
    /**
     * @var IOInterface
     */
    private $io;
    /**
     * @var UrlMapper
     */
    private $urlMapper;

    public function __construct(Composer $composer, IOInterface $io, UrlMapper $urlMapper)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->urlMapper = $urlMapper;
    }

    public function applyPluginFix(object $plugin): void
    {
        $composer = $this->composer;
        $io = $this->io;
        $urlMapper = $this->urlMapper;

        $wrapRfs = Closure::bind(function () use ($composer, $io, $urlMapper): void {
            $oldRfs = $this->rfs;
            $velocitaRfs = new SymfonyFlexFilesystem(
                $urlMapper,
                $io,
                $composer->getConfig(),
                $oldRfs->getOptions(),
                $oldRfs->isTlsDisabled()
            );
            $this->rfs = $velocitaRfs;

            $fixDownloader = Closure::bind(function () use ($velocitaRfs): void {
                $this->rfs = $velocitaRfs;
            }, $this->downloader, Downloader::class);
            $fixDownloader();
        }, $plugin, Flex::class);
        $wrapRfs();

        $this->io->write(\sprintf('%s(): successfully wrapped Flex RFS', __METHOD__), true, IOInterface::DEBUG);
    }
}
