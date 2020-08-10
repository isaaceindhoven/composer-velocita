<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;
use RuntimeException;
use Symfony\Flex\Flex;
use UnexpectedValueException;

use function sprintf;

/**
 * Symfony Flex and Velocita work great together, but the parallel dist file prefetcher in Flex is implemented as a new
 * RemoteFilesystem that completely bypasses any RFS already in place. Velocita fixes compatibility with Flex by
 * replacing their RemoteFilesystem with our own extension, which then maps URLs to the Velocita proxy.
 */
class SymfonyFlexCompatibility implements CompatibilityFix
{
    /**
     * @var CompatibilityDetector
     */
    private $compatibilityDetector;

    public function __construct(CompatibilityDetector $compatibilityDetector)
    {
        $this->compatibilityDetector = $compatibilityDetector;
    }

    public function applyPluginFix(PluginInterface $plugin): void
    {
        if (!$plugin instanceof Flex) {
            throw new UnexpectedValueException('Plugin must be instance of Flex');
        }

        $downloaderProperty = $this->getAccessibleProperty($plugin, 'downloader');
        $downloader = $downloaderProperty->getValue($plugin);

        $rfsProperty = $this->getAccessibleProperty($downloader, 'rfs');
        $oldRfs = $rfsProperty->getValue($downloader);
        if ($oldRfs instanceof SymfonyFlexFilesystem) {
            return;
        }

        $io = $this->compatibilityDetector->getIo();
        $disableTlsProperty = $this->getAccessibleProperty($oldRfs, 'disableTls');
        $rfsProperty->setValue($downloader, new SymfonyFlexFilesystem(
            $this->compatibilityDetector->getUrlMapper(),
            $io,
            $this->compatibilityDetector->getComposer()->getConfig(),
            $oldRfs->getOptions(),
            $disableTlsProperty->getValue($oldRfs)
        ));

        $io->write(sprintf('%s(): successfully wrapped Flex RFS', __METHOD__), true, IOInterface::DEBUG);
    }

    protected function getAccessibleProperty(object $object, string $propertyName): ReflectionProperty
    {
        $reflectionObject = new ReflectionObject($object);
        try {
            $reflectionProperty = $reflectionObject->getProperty($propertyName);
        } catch (ReflectionException $e) {
            throw new RuntimeException(sprintf('Property `%s` could not be found', $propertyName), 0, $e);
        }
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty;
    }
}
