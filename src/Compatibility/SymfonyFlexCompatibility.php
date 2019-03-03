<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

use Closure;
use Composer\Composer;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Installer\InstallerEvents;
use Composer\IO\IOInterface;
use ISAAC\Velocita\Composer\Composer\PluginHelper;

/**
 * Symfony Flex and Velocita work great together; however, Flex installs an event listener with the highest priority
 * possible which conflicts with Velocita's desire to update packages' distribution URLs. To make sure Velocita runs
 * before Flex does, we decrease the priority of Flex's event listener.
 */
class SymfonyFlexCompatibility implements CompatibilityFix
{
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

    public function applyFix(): void
    {
        $eventName = InstallerEvents::POST_DEPENDENCIES_SOLVING;
        $priority = \PHP_INT_MAX;
        $pluginClass = 'Symfony\\Flex\\Flex';

        $eventDispatcher = $this->composer->getEventDispatcher();
        $flexListener = $this->findListener($eventDispatcher, $eventName, $priority, $pluginClass);
        if ($flexListener === null) {
            return;
        }

        $this->removeListener($eventDispatcher, $eventName, $priority, $flexListener);

        $newPriority = $priority - 1;
        $eventDispatcher->addListener($eventName, $flexListener, $newPriority);

        $this->io->write(
            \sprintf('%s(): successfully deprioritized %s', __METHOD__, $pluginClass),
            true,
            IOInterface::DEBUG
        );
    }

    private function findListener(
        EventDispatcher $eventDispatcher,
        string $eventName,
        int $priority,
        string $class
    ): ?callable {
        $findListener = Closure::bind(function () use ($eventName, $priority, $class): ?callable {
            if (!\array_key_exists($eventName, $this->listeners)
                    || !\array_key_exists($priority, $this->listeners[$eventName])) {
                return null;
            }

            foreach ($this->listeners[$eventName][$priority] as $listener) {
                if (\is_array($listener) && \is_callable($listener)
                        && PluginHelper::getOriginalClassName(\get_class($listener[0])) === $class) {
                    return $listener;
                }
            }
            return null;
        }, $eventDispatcher, EventDispatcher::class);
        return $findListener();
    }

    private function removeListener(
        EventDispatcher $eventDispatcher,
        string $eventName,
        int $priority,
        callable $listener
    ): void {
        $removeListener = Closure::bind(function () use ($eventName, $priority, $listener): void {
            $this->listeners[$eventName][$priority] = \array_values(
                \array_filter(
                    $this->listeners[$eventName][$priority],
                    function ($currentListener) use ($listener): bool {
                        return $currentListener !== $listener;
                    }
                )
            );
        }, $eventDispatcher, EventDispatcher::class);
        $removeListener();
    }
}
