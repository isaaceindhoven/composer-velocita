<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

interface CompatibilityFix
{
    /**
     * @param mixed $plugin
     */
    public function applyPluginFix($plugin): void;
}
