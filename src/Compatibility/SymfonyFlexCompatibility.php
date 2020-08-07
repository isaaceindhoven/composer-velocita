<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

use Closure;
use Composer\IO\IOInterface;

use function get_class;
use function sprintf;

/**
 * Symfony Flex and Velocita work great together, but the parallel dist file prefetcher in Flex is implemented as a new
 * RemoteFilesystem that completely bypasses any RFS already in place. Velocita fixes compatibility with Flex by
 * replacing their RemoteFilesystem with our own extension, which then redirects URLs to the Velocita proxy.
 */
class SymfonyFlexCompatibility implements CompatibilityFix
{
    /**
     * @var CompatibilityDetector
     */
    private $compatibilityDetector;

    public function __construct(CompatibilityDetector $compatibilityDetector)
    {
        $this->compatibilityDetector = $compatibilityDetector;
    }

    /**
     * @inheritdoc
     */
    public function applyPluginFix($plugin): void
    {
        $composer = $this->compatibilityDetector->getComposer();
        $io = $this->compatibilityDetector->getIo();
        $urlMapper = $this->compatibilityDetector->getUrlMapper();

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
            }, $this->downloader, get_class($this->downloader));
            $fixDownloader();
        }, $plugin, get_class($plugin));
        $wrapRfs();

        $io->write(sprintf('%s(): successfully wrapped Flex RFS', __METHOD__), true, IOInterface::DEBUG);
    }
}
