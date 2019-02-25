<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Util;

use Composer\Factory;

class ComposerFactory extends Factory
{
    /**
     * Exposes the protected ComposerFactory::getHomeDir().
     */
    public static function getComposerHomeDir(): string
    {
        return parent::getHomeDir();
    }
}
