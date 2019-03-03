<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

use Composer\Composer;
use Composer\IO\IOInterface;
use ISAAC\Velocita\Composer\Composer\PluginHelper;

class CompatibilityDetector
{
    private const PLUGIN_CLASS_COMPATIBILITY = [
        'Symfony\\Flex\\Flex' => SymfonyFlexCompatibility::class,
    ];

    /**
     * @var Composer
     */
    private $composer;
    /**
     * @var IOInterface
     */
    private $io;

    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function fixCompatibility(): void
    {
        $this->fixPluginCompatibility();
    }

    private function fixPluginCompatibility(): void
    {
        $pluginManager = $this->composer->getPluginManager();
        foreach ($pluginManager->getPlugins() as $plugin) {
            $pluginClass = PluginHelper::getOriginalClassName(\get_class($plugin));

            if (!\array_key_exists($pluginClass, static::PLUGIN_CLASS_COMPATIBILITY)) {
                continue;
            }
            $fixClass = static::PLUGIN_CLASS_COMPATIBILITY[$pluginClass];

            $this->io->write(
                \sprintf('%s(): plugin %s detected; running compatibility fix %s', __METHOD__, $pluginClass, $fixClass),
                true,
                IOInterface::DEBUG
            );

            /** @var CompatibilityFix $fixInstance */
            $fixInstance = new $fixClass($this->composer, $this->io);
            $fixInstance->applyFix();
        }
    }
}
