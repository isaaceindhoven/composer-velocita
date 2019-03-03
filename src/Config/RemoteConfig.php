<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Config;

class RemoteConfig
{
    /**
     * @var MirrorMapping[]
     */
    protected $mirrors = [];

    public static function fromArray(array $data): RemoteConfig
    {
        $config = new RemoteConfig();

        $mirrors = $data['mirrors'] ?? [];
        foreach ($mirrors as $mappingData) {
            $config->addMirror(MirrorMapping::fromArray($mappingData));
        }

        return $config;
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
