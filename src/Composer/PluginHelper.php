<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Composer;

use LogicException;

class PluginHelper
{
    private const COMPOSER_CLONE_REGEX = '#_composer_tmp\\d+$#';

    public static function getOriginalClassName(string $pluginClass): string
    {
        if (\preg_match(static::COMPOSER_CLONE_REGEX, $pluginClass) < 1) {
            return $pluginClass;
        }
        $originalClassName = \preg_replace(static::COMPOSER_CLONE_REGEX, '', $pluginClass);
        if ($originalClassName === null) {
            throw new LogicException('Failed to reconstruct original class name');
        }
        return $originalClassName;
    }
}
