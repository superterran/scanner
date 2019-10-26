<?php

namespace Superterran\Scanner;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;

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
        
        $config = $this->getConfig();

        if ($target) {
            $output->writeln("Target: " .$target);

            if(isset($config[$target.'.yml'])) {
                $this->scanTarget($config[$target.'.yml']);
            } else {
                $output->writeln("Failed to find target " . $target);
                return 255;
            }                
        } else {
            $output->writeln("Run all");
            
            var_dump($config);
          //  $this->scanTarget($target);
        }
        
        return true;
    }

    protected function getConfig()
    {

        $config = array();

        $finder = new Finder();

        $finder->files()->in(__DIR__.'/../targets/')->name('*.yml');

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $config[$file->getFileName()] = Yaml::parseFile($file->getRealPath());
            }
        }

        return $config;
    }

    public function scanTarget($targetConfig) 
    {
        foreach ($targetConfig as $target) {
            if (isset($target['url'])) {

                var_dump($target['url']);    
            }
            
        }
    }
}
