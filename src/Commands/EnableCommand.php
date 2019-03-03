<?php

declare(strict_types=1);

namespace ISAAC\Velocita\Composer\Commands;

use Composer\Command\BaseCommand;
use ISAAC\Velocita\Composer\VelocitaPlugin;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnableCommand extends BaseCommand
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
            ->setName('velocita:enable')
            ->setDescription('Enables the Velocita plugin')
            ->addArgument('url', InputArgument::OPTIONAL, 'Sets the URL to your Velocita instance');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $inputAdapter = new InputInterfaceAdapter($input);
        $url = $inputAdapter->getStringArgument('url');

        // Update configuration
        $config = $this->plugin->getConfiguration();
        $config->setEnabled(true);
        if ($url !== null) {
            $config->setURL($url);
        }

        // Write new configuration
        $this->plugin->writeConfiguration($config);

        $output->writeln('Velocita is now enabled.');

        return null;
    }
}
