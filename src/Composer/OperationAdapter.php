<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Composer;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\MarkAliasInstalledOperation;
use Composer\DependencyResolver\Operation\MarkAliasUninstalledOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\AliasPackage;
use Composer\Package\Package;
use UnexpectedValueException;

class OperationAdapter
{
    /**
     * @var OperationInterface
     */
    private $operation;

    public function __construct(OperationInterface $operation)
    {
        $this->operation = $operation;
    }

    public function getPackage(): Package
    {
        if (($this->operation instanceof InstallOperation)
                || ($this->operation instanceof MarkAliasInstalledOperation)
                || ($this->operation instanceof MarkAliasUninstalledOperation)
                || ($this->operation instanceof UninstallOperation)) {
            $package = $this->operation->getPackage();
        } elseif ($this->operation instanceof UpdateOperation) {
            $package = $this->operation->getTargetPackage();
        } else {
            throw new UnexpectedValueException('Unexpected operation type');
        }

        // Resolve aliases
        while ($package instanceof AliasPackage) {
            $package = $package->getAliasOf();
        }

        if (!$package instanceof Package) {
            throw new UnexpectedValueException('Unexpected package type');
        }
        return $package;
    }
}
