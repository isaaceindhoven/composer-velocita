<?php

namespace ISAAC\Velocita\Composer\Util;

use Composer\Factory;

/**
 * ComposerFactory exposes some inaccessible functionality of Composer's Factory class.
 */
class ComposerFactory extends Factory
{
    public static function getHomeDir(): string
    {
        return parent::getHomeDir();
    }
}
