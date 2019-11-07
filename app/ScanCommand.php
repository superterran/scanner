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

class ScanCommand extends Command
{

    public $output = false;
    public $whitelist = array();

    public const ignoreEntryTypes = ['paint', 'measure', 'mark'];

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

        $this->clamscanInit();

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
        

        $this->clamscan();

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

        $tmpdir = getcwd()."/tmp/";

        $driver = RemoteWebDriver::create($host, $capabilities);

        $targets = $targetConfig['targets'];

        $whitelist = $this->getWhitelist();

        $downloadList = array();

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

                    if ($asset['entryType'] == "resource") {

                        $downloadList[] = $asset['name'];
                    }                    
                    
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


        # lets run clamav

        $this->downloadList($downloadList);
 
        
    }

    protected function clamscanInit()
    {

        $tmpdir = getcwd()."/tmp/";

        if (!is_dir($tmpdir)) {
            if ($this->output->isDebug()) {
                $this->output->writeln('creating '.$tmpdir);
            }
            mkdir(getcwd()."/tmp/", 0700);
        } else {
            if ($this->output->isDebug()) {
                $this->output->writeln('purging '.$tmpdir);
            }
            array_map( 'unlink', array_filter((array) glob(getcwd()."/tmp/*.supa") ) );
        }

    }

    protected function clamscan() 
    {
        $tmpdir = getcwd()."/tmp/";

        $this->output->writeln("\n\n Running clamscan against ".$tmpdir."\n");
        echo shell_exec("clamscan ".getcwd()."/tmp/");

        if ($this->output->isDebug()) {
            $this->output->writeln('purging '.$tmpdir);
        }
        // array_map( 'unlink', array_filter((array) glob(getcwd()."/tmp/*.supa") ) );
    }

    protected function downloadList($list)
    {
        foreach($list as $item) {
            file_put_contents(getcwd()."/tmp/".uniqid().'.supa', fopen($item, 'r'));
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
                $this->output->writeln(print_r($this->whitelist));
            }
        }
        
        return $this->whitelist;
    }
}
