<?php

namespace Superterran\Scanner\Tests;

use PHPUnit\Framework\TestCase;

abstract class AbstractTest extends TestCase
{
    protected function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
    
        return $method->invokeArgs($object, $parameters);
    }
}
