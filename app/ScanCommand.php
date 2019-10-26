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
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

class ScanCommand extends Command
{

    public $whitelist = array();

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

        $host = 'http://localhost:4444/wd/hub';
      
        $driver = RemoteWebDriver::create($host, DesiredCapabilities::firefox());

        foreach ($targetConfig as $target) {
            if (isset($target['url'])) {
                $driver->navigate()->to($target['url']);
            }            
        }
        $network = $driver->executeScript("return window.performance.getEntries();");

        

        foreach($network as $asset)
        {         
            $match = false;   
            if($asset['initiatorType'] == 'script') {
                foreach (array_merge($this->getWhitelist(), $target['whitelist']) as $whitelist) {
                    if(strpos($asset['name'], $whitelist) !== false) {
                        $match = true;
                    }
                }                     

                if (!$match) var_dump($asset['name']);   
                
            }
        }

    }

    protected function getWhitelist()
    {

        if(empty($this->whitelist)) {

            $config = array();

            $finder = new Finder();
    
            $finder->files()->in(__DIR__.'/../')->name('whitelist.yml');
    
            if ($finder->hasResults()) {
                foreach ($finder as $file) {
                    $whitelist = Yaml::parseFile($file->getRealPath());
                }
            }
            $this->whitelist = $whitelist;    
        }
        
        return $this->whitelist;
    }
}
