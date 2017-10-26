<?php

namespace ISAAC\Velocita\Composer\Config;

class Endpoints
{
    /** @var EndpointMapping[] */
    protected $repositories = [];

    /** @var EndpointMapping[] */
    protected $distributionChannels = [];

    public static function fromArray(array $data): Endpoints
    {
        $endpoints = new Endpoints();

        $repos = $data['repositories'] ?? [];
        foreach ($repos as $mappingData) {
            $endpoints->addRepository(EndpointMapping::fromArray($mappingData));
        }

        $dists = $data['distributionChannels'] ?? [];
        foreach ($dists as $mappingData) {
            $endpoints->addDistributionChannel(EndpointMapping::fromArray($mappingData));
        }

        return $endpoints;
    }

    public function addRepository(EndpointMapping $mapping)
    {
        $this->repositories[] = $mapping;
    }

    public function addDistributionChannel(EndpointMapping $mapping)
    {
        $this->distributionChannels[] = $mapping;
    }

    /**
     * @return EndpointMapping[]
     */
    public function getRepositories(): array
    {
        return $this->repositories;
    }

    /**
     * @return EndpointMapping[]
     */
    public function getDistributionChannels(): array
    {
        return $this->distributionChannels;
    }
}
