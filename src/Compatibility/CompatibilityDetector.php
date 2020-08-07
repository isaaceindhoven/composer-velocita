<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use ISAAC\Velocita\Composer\Composer\OperationAdapter;
use ISAAC\Velocita\Composer\Composer\PluginHelper;
use ISAAC\Velocita\Composer\UrlMapper;

use function array_key_exists;
use function get_class;
use function sprintf;

class CompatibilityDetector
{
    private const PACKAGE_INSTALL_TRIGGERS = [
        'hirak/prestissimo' => true,
        'symfony/flex' => true,
    ];
    private const PLUGIN_CLASS_COMPATIBILITY = [
        'Hirak\\Prestissimo\\Plugin' => PrestissimoCompatibility::class,
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
    /**
     * @var UrlMapper
     */
    private $urlMapper;

    public function __construct(Composer $composer, IOInterface $io, UrlMapper $urlMapper)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->urlMapper = $urlMapper;
    }

    public function getComposer(): Composer
    {
        return $this->composer;
    }

    public function getIo(): IOInterface
    {
        return $this->io;
    }

    public function getUrlMapper(): UrlMapper
    {
        return $this->urlMapper;
    }

    public function fixPluginCompatibility(): void
    {
        $pluginManager = $this->composer->getPluginManager();
        foreach ($pluginManager->getPlugins() as $plugin) {
            $pluginClass = PluginHelper::getOriginalClassName(get_class($plugin));

            if (!array_key_exists($pluginClass, static::PLUGIN_CLASS_COMPATIBILITY)) {
                continue;
            }
            $fixClass = static::PLUGIN_CLASS_COMPATIBILITY[$pluginClass];

            $this->io->write(
                sprintf('%s(): plugin %s detected; running compatibility fix %s', __METHOD__, $pluginClass, $fixClass),
                true,
                IOInterface::DEBUG
            );

            /** @var CompatibilityFix $fixInstance */
            $fixInstance = new $fixClass($this);
            $fixInstance->applyPluginFix($plugin);
        }
    }

    public function onPackageInstall(PackageEvent $event): void
    {
        $operation = new OperationAdapter($event->getOperation());
        $package = $operation->getPackage();
        $packageName = $package->getName();

        if (!array_key_exists($packageName, static::PACKAGE_INSTALL_TRIGGERS)) {
            return;
        }

        $this->fixPluginCompatibility();
    }
}
