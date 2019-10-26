<?php

namespace Superterran\Scanner\Tests;

use PHPUnit\Framework\TestCase;
use Superterran\Scanner\ScanCommand;

class ScanCommandTest extends TestCase
{
    public function testInstantiates()
    {
        $obj = new ScanCommand();
        $this->assertInstanceOf("Superterran\Scanner\ScanCommand", $obj);
    }
}
