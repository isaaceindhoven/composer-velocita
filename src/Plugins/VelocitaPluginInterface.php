<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Plugins;

use Composer\Plugin\PluginInterface;
use ISAAC\Velocita\Composer\Config\Endpoints;
use ISAAC\Velocita\Composer\Config\PluginConfig;

interface VelocitaPluginInterface extends PluginInterface
{
    public function getConfiguration(): PluginConfig;

    public function getEndpoints(): Endpoints;
}
