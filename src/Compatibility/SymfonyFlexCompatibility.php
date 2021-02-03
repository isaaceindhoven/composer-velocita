<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Compatibility;

use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\HttpDownloader;
use InvalidArgumentException;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;
use RuntimeException;
use Symfony\Flex\Downloader;
use Symfony\Flex\Flex;
use Symfony\Flex\ParallelDownloader;
use UnexpectedValueException;

use function get_class;
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
            throw new InvalidArgumentException('Plugin must be instance of Flex');
        }

        $io = $this->compatibilityDetector->getIo();
        $downloaderProperty = $this->getAccessibleProperty($plugin, 'downloader');
        $downloader = $downloaderProperty->getValue($plugin);

        $rfsProperty = $this->getAccessibleProperty($downloader, 'rfs');
        $rfs = $rfsProperty->getValue($downloader);

        if ($rfs instanceof HttpDownloader) {
            $this->applyHttpDownloaderFix($io, $downloader, $rfs, $rfsProperty);
        } elseif ($rfs instanceof ParallelDownloader) {
            $this->applyParallelDownloaderFix($io, $downloader, $rfs, $rfsProperty);
        } else {
            throw new UnexpectedValueException(sprintf('Unsupported Symfony Flex RFS: %s', get_class($rfs)));
        }

        $io->write(sprintf('%s(): successfully wrapped Flex RFS', __METHOD__), true, IOInterface::DEBUG);
    }

    protected function getAccessibleProperty(object $object, string $propertyName): ReflectionProperty
    {
        $reflectionObject = new ReflectionObject($object);
        try {
            $reflectionProperty = $reflectionObject->getProperty($propertyName);
        } catch (ReflectionException $e) {
            throw new RuntimeException(
                sprintf('Property `%s::$%s` could not be found', get_class($object), $propertyName),
                0,
                $e
            );
        }
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty;
    }

    /**
     * ParallelDownloader is used in Composer <2.0.0.
     */
    protected function applyParallelDownloaderFix(
        IOInterface $io,
        Downloader $downloader,
        ParallelDownloader $rfs,
        ReflectionProperty $rfsProperty
    ): void {
        // Already patched?
        if ($rfs instanceof SymfonyFlexFilesystem) {
            return;
        }

        $rfsProperty->setValue($downloader, new SymfonyFlexFilesystem(
            $this->compatibilityDetector->getUrlMapper(),
            $io,
            $this->compatibilityDetector->getComposer()->getConfig(),
            $rfs->getOptions(),
            $this->getAccessibleProperty($rfs, 'disableTls')->getValue($rfs)
        ));
    }

    protected function applyHttpDownloaderFix(
        IOInterface $io,
        Downloader $downloader,
        HttpDownloader $rfs,
        ReflectionProperty $rfsProperty
    ): void {
        // Already patched?
        if ($rfs instanceof SymfonyFlexHttpDownloader) {
            return;
        }

        $rfsProperty->setValue($downloader, new SymfonyFlexHttpDownloader(
            $this->compatibilityDetector->getUrlMapper(),
            $io,
            $this->compatibilityDetector->getComposer()->getConfig(),
            $rfs->getOptions(),
            $this->getAccessibleProperty($rfs, 'rfs')->getValue($rfs)->isTlsDisabled()
        ));
    }
}
