<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

use Hirak\Prestissimo\Plugin as PrestissimoPlugin;

use function sprintf;

/**
 * Prestissimo's prefetcher is instantiated inline which makes patching it relatively hard, and since running Velocita
 * and Prestissimo in parallel results in diminishing returns, we are better off just disabling it.
 */
class PrestissimoCompatibility implements CompatibilityFix
{
    /**
     * @var CompatibilityDetector
     */
    protected $compatibilityDetector;

    public function __construct(CompatibilityDetector $compatibilityDetector)
    {
        $this->compatibilityDetector = $compatibilityDetector;
    }

    /**
     * @inheritdoc
     */
    public function applyPluginFix($plugin): void
    {
        /** @var PrestissimoPlugin $plugin */
        $plugin->disable();

        $this->compatibilityDetector->getIo()->writeError(
            sprintf('<warning>Disabled %s - incompatible with Velocita</warning>', PrestissimoPlugin::class)
        );
    }
}
