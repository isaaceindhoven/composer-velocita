<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Composer;

use Composer\Package\Package;

class PackageAdapter
{
    /**
     * @var Package
     */
    private $package;

    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    public function getPrimaryUrl(): ?string
    {
        $distUrls = $this->package->getDistUrls();
        if (\count($distUrls) <= 0) {
            return null;
        }
        return \current($distUrls);
    }
}
