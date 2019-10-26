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

    public $output = false;
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

        $this->output = &$output;

        $target = $input->getArgument('target');
        
        $config = $this->getConfig();

        if ($target) {
            if(isset($config[$target.'.yml'])) {
                $this->scanTarget($config[$target.'.yml']);
            } else {
                $output->writeln("Failed to find target " . $target);
                return 255;
            }                
        } else {
            $output->writeln("Run all");
            
            foreach($config as $target) {
                $this->scanTarget($target);
            }
        }
        
        return true;
    }

    protected function getConfig()
    {

        $config = array();

        $finder = new Finder();

        $finder->files()->in(getcwd().'/targets/')->name('*.yml');

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

        $targets = $targetConfig['targets'];

        $whitelist = $this->getWhitelist();

        if(isset($targetConfig['whitelist'])) {
            foreach ($targetConfig['whitelist'] as $item) {
                if(!in_array($item, $whitelist)) {
                    $whitelist[] = $item;
                }
            }
        }

        foreach ($targets as $target) {
            if (isset($target['url'])) {

                if (isset($target['url'])) {
                   $this->output->writeln('* Target: '.$target['url']);
                }

                $driver->navigate()->to($target['url']);
            }            
        }
        $network = $driver->executeScript("return window.performance.getEntries();");

        foreach($network as $asset)
        {         
            if ($this->output->isVerbose()) {
                $this->output->writeln('asset: '. print_r($asset));
            }

            if(isset($asset['name'])) {
                $match = false;   
                // if($asset['initiatorType'] == 'script') {
                    foreach ($whitelist as $whiteurl) {
                        if(strpos($asset['name'], $whiteurl) !== false) {
                            $match = true;
                        }
                    }                     
    
                    if (!$match) {
                        $this->output->writeln('fails - '.$asset['name']);   
                    }
                    
                // }
            } else {
                throw new \Exception("Assets aren't returning from the webdriver!");
            }
        }

    }

    protected function getWhitelist()
    {

        if(empty($this->whitelist)) {

            $config = array();

            $finder = new Finder();
    
            $finder->files()->in(getcwd())->name('whitelist.yml');
    
            if ($finder->hasResults()) {
                foreach ($finder as $file) {
  
                    if ($this->output->isDebug()) {
                        $this->output->writeln('fetching whitelist from: '.$file);
                    }
  
                    $whitelist = Yaml::parseFile($file->getRealPath());

                    foreach ($whitelist as $item) {
                        if(!in_array($item, $this->whitelist)) {
                            $this->whitelist[] = $item;
                        }
                    }
                }
            }

            if ($this->output->isDebug()) {
                $this->output->writeln('final whitelist: '. print_r($this->whitelist));
            }
        }
        
        return $this->whitelist;
    }
}
