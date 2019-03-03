<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Config;

class Endpoints
{
    /**
     * @var MirrorMapping[]
     */
    protected $mirrors = [];

    public static function fromArray(array $data): Endpoints
    {
        $endpoints = new Endpoints();

        $mirrors = $data['mirrors'] ?? [];
        foreach ($mirrors as $mappingData) {
            $endpoints->addMirror(MirrorMapping::fromArray($mappingData));
        }

        return $endpoints;
    }

    public function addMirror(MirrorMapping $mapping): void
    {
        $this->mirrors[] = $mapping;
    }

    /**
     * @return MirrorMapping[]
     */
    public function getMirrors(): array
    {
        return $this->mirrors;
    }
}
