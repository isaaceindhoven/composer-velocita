<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Config;

use UnexpectedValueException;

use function array_key_exists;

class RemoteConfig
{
    /**
     * @var MirrorMapping[]
     */
    protected array $mirrors = [];

    /**
     * @param array{mirrors?: array{url?: string, path?: string}[]} $data
     */
    public static function fromArray(array $data): RemoteConfig
    {
        $config = new RemoteConfig();

        if (!array_key_exists('mirrors', $data)) {
            throw new UnexpectedValueException('Missing `mirrors` key in data');
        }
        foreach ($data['mirrors'] as $mappingData) {
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
