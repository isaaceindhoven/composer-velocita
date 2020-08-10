<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

use Composer\Plugin\PluginInterface;

interface CompatibilityFix
{
    public function applyPluginFix(PluginInterface $plugin): void;
}
