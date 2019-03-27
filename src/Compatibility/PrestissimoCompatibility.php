<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

use Composer\Composer;
use Composer\IO\IOInterface;
use Hirak\Prestissmo\Plugin as PrestissimoPlugin;
use ISAAC\Velocita\Composer\UrlMapper;

/**
 * Prestissimo's prefetcher is instantiated inline which makes patching it relatively hard, and since running Velocita
 * and Prestissimo in parallel results in diminishing returns, we are better off just disabling it.
 */
class PrestissimoCompatibility implements CompatibilityFix
{
    /**
     * @var IOInterface
     */
    protected $io;

    public function __construct(Composer $composer, IOInterface $io, UrlMapper $urlMapper)
    {
        $this->io = $io;
    }

    /**
     * @inheritdoc
     */
    public function applyPluginFix($plugin): void
    {
        /** @var PrestissimoPlugin $plugin */
        $plugin->disable();

        $this->io->writeError(
            \sprintf('<warning>Disabled %s - incompatible with Velocita</warning>', PrestissimoPlugin::class)
        );
    }
}
