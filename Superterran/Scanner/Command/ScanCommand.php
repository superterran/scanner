<?php

namespace Superterran\Scanner\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ScanCommand extends Command
{

    protected static $defaultName = 'scan';
    /**
     * Apply a branch non-required argument to all classes that will use this.
     * Parent::configure() is required to use.
     *
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('target', InputArgument::OPTIONAL, 'Which target should we point at?')
            ->setDescription('triggers a scan')
            ->setHelp('This command allows you to run all tests in the suite');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int exit code
     * @SuppressWarnings("unused")
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument('target');

        if ($target) {
            $output->writeln("Target: " .$target);
        } else {
            $output->writeln("Run all");
        }
        
        return;
    }
}
