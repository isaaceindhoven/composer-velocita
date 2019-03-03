<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

interface CompatibilityFix
{
    public function applyFix(): void;
}
