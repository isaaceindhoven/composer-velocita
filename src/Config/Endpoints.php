<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Config;

class Endpoints
{
    /**
     * @var EndpointMapping[]
     */
    protected $repositories = [];

    public static function fromArray(array $data): Endpoints
    {
        $endpoints = new Endpoints();

        $repos = $data['mirrors'] ?? [];
        foreach ($repos as $mappingData) {
            $endpoints->addRepository(EndpointMapping::fromArray($mappingData));
        }

        return $endpoints;
    }

    public function addRepository(EndpointMapping $mapping): void
    {
        $this->repositories[] = $mapping;
    }

    /**
     * @return EndpointMapping[]
     */
    public function getRepositories(): array
    {
        return $this->repositories;
    }
}
