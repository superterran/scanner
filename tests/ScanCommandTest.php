<?php

namespace Superterran\Scanner\Tests;

use PHPUnit\Framework\TestCase;
use Superterran\Scanner\ScanCommand;

class ScanCommandTest extends AbstractTest
{
    public $exampleFilename = __DIR__.'/../targets/fixture.yml';
    public $exampleContents = <<<EOD
---
- url: https://wwww.google.com/
  params:
    - key: hello
      value: world
      type: GET
  headers:
    - key: header
      value: world
- url: https://wwww.google.com/checkout/
  params:
    - key: hello
      value: world
      type: GET
  headers:
    - key: header
      value: world
EOD;

    protected function setUp() : void
    {
        $handle = fopen($this->exampleFilename, 'w');
        fwrite($handle, $this->exampleContents);
        fclose($handle);
    }

    protected function tearDown() : void
    {
       unlink($this->exampleFilename);
    }

    public function testInstantiates()
    {
        $obj = new ScanCommand();
        $this->assertInstanceOf("Superterran\Scanner\ScanCommand", $obj);
    }

    public function testConfig()
    {
        $obj = new ScanCommand();
        $config = $this->invokeMethod($obj, 'getConfig');
        $this->assertArrayHasKey('fixture.yml', $config);

        $piece = $config['fixture.yml'];

        foreach ($piece as $target) {
            $url = parse_url($target['url']);
            $this->assertArrayHasKey('scheme', $url);
            $this->assertArrayHasKey('host', $url);
            $this->assertArrayHasKey('path', $url);
            
            $this->assertArrayHasKey('headers', $target);
            $this->assertArrayHasKey('params', $target);

            $headers = $target['headers'][0];
            $this->assertArrayHasKey('key', $headers);
            $this->assertArrayHasKey('value', $headers);

            $params = $target['params'][0];
            $this->assertArrayHasKey('key', $params);
            $this->assertArrayHasKey('value', $params);
            $this->assertArrayHasKey('type', $params);
        }
        
     
    }

}

