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

class ScanUrlCommand extends ScanCommand
{

    protected static $defaultName = 'scan:url';

    /**
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

        $url = $input->getArgument('url');
        
        $config = $this->getConfig();


        $output->writeln("Running against ".$url);
            
        $target = array(
            'targets' => array(array('url' => 'https://'.$url.'/')),
            'whitelist' => array('//'.$url.'/', '//www.'.$url.'/')
        );

        $this->clamscanInit();

        $this->scanTarget($target);

        $this->clamscan();
        
        return true;
    }
}
