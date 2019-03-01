<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Commands;

use Composer\Command\BaseCommand;
use ISAAC\Velocita\Composer\VelocitaPlugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DisableCommand extends BaseCommand
{
    /**
     * @var VelocitaPlugin
     */
    protected $plugin;

    public function __construct(VelocitaPlugin $plugin)
    {
        parent::__construct();

        $this->plugin = $plugin;
    }

    protected function configure(): void
    {
        $this
            ->setName('velocita:disable')
            ->setDescription('Disables the Velocita plugin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        // Update configuration
        $config = $this->plugin->getConfiguration();
        $config->setEnabled(false);

        // Write new configuration
        $this->plugin->writeConfiguration($config);

        $output->writeln('Velocita is now disabled.');

        return null;
    }
}
