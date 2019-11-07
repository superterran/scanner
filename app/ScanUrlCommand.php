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
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;

class ScanUrlCommand extends Command
{

    public $output = false;
    public $whitelist = array();

    public const ignoreEntryTypes = ['paint', 'measure', 'mark'];

    protected static $defaultName = 'scan:url';
    /**
     * Apply a branch non-required argument to all classes that will use this.
     * Parent::configure() is required to use.
     *
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('url', InputArgument::REQUIRED, 'Which url to scan?')
            ->setDescription('triggers a scan, with default, settings, against a given url')
            ->setHelp('This command allows you to pass in a url and have it treated as a target');
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

        $url = $input->getArgument('target');
        
        $config = $this->getConfig();


        $output->writeln("Running against ".$url);
            
        $target = array(
            'targets' => array(array('url' => 'https://'.$url.'/')),
            'whitelist' => array('//'.$url.'/', '//www.'.$url.'/')
        );

        $this->scanTarget($target);

        
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
        $capabilities = DesiredCapabilities::chrome();

        $options = new ChromeOptions();
        $options->setBinary('/usr/bin/google-chrome');
        $options->addArguments(["--headless","--disable-gpu", "--no-sandbox"]);
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $capabilities->setPlatform("Linux");


        $driver = RemoteWebDriver::create($host, $capabilities);

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
        
            $network = $driver->executeScript("return window.performance.getEntries();");
            

            foreach($network as $asset)
            {         
                if (isset($asset['entryType']) && !in_array($asset['entryType'], self::ignoreEntryTypes)) {

                    if ($this->output->isDebug()) {
                        $this->output->writeln('asset: ');
                        $this->output->writeln(print_r($asset));
                    }
    
                    if(isset($asset['name'])) {
                        $match = false;   

                        foreach ($whitelist as $whiteurl) {
                            if(strpos($asset['name'], $whiteurl) !== false) {
                                $match = true;
                            }
                        }                     
        
                        if (!$match) {
                            $this->output->writeln('fails - '.$asset['name']);
                            if ($this->output->isVerbose()) {
                                $this->output->writeln(print_r($asset));
                            }   
                        }
                        

                    } else {
                        throw new \Exception("Assets aren't returning from the webdriver!");
                    }
                } else {
                    if ($this->output->isDebug()) {
                        $this->output->writeln('ignoring '.$asset['name'].' because entry is '.$asset['entryType']);
                        $this->output->writeln(print_r($asset));
                    }
                }
            }
        }

        $driver->quit();
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
                $this->output->writeln(print_r($this->whitelist));
            }
        }
        
        return $this->whitelist;
    }
}
