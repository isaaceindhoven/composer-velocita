<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Commands;

use Symfony\Component\Console\Input\InputInterface;
use UnexpectedValueException;

class InputInterfaceAdapter
{
    /**
     * @var InputInterface
     */
    private $input;

    public function __construct(InputInterface $input)
    {
        $this->input = $input;
    }

    public function getStringArgument(string $name): ?string
    {
        $value = $this->input->getArgument($name);
        if (\is_array($value)) {
            throw new UnexpectedValueException(
                \sprintf('Expected string but got array for input argument "%s"', $name)
            );
        }
        return $value;
    }
}
