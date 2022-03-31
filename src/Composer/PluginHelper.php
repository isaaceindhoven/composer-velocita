<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Composer;

use LogicException;

use function preg_match;
use function preg_replace;

class PluginHelper
{
    private const COMPOSER_CLONE_REGEX = '#_composer_tmp\\d+$#';

    /**
     * Reverts any class name modifications done by Composer's PluginManager because of their cloning behavior.
     *
     * See: https://github.com/composer/composer/blob/9d139cb694b0f08a4ba535395c16a00b2968ac7b/src/Composer/Plugin/PluginManager.php#L190
     */
    public static function getOriginalClassName(string $pluginClass): string
    {
        if (preg_match(self::COMPOSER_CLONE_REGEX, $pluginClass) < 1) {
            return $pluginClass;
        }
        $originalClassName = preg_replace(self::COMPOSER_CLONE_REGEX, '', $pluginClass);
        if ($originalClassName === null) {
            throw new LogicException('Failed to reconstruct original class name');
        }
        return $originalClassName;
    }
}
