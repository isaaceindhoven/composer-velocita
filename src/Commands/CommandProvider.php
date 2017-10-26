<?php

namespace ISAAC\Velocita\Composer\Commands;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use ISAAC\Velocita\Composer\Plugins\VelocitaPlugin;

class CommandProvider implements CommandProviderCapability
{
    /** @var VelocitaPlugin */
    protected $plugin;

    public function __construct(array $arguments)
    {
        $this->plugin = $arguments['plugin'];
    }

    public function getCommands(): array
    {
        return [
            new EnableCommand($this->plugin),
            new DisableCommand($this->plugin),
        ];
    }
}
