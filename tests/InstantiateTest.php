<?php

namespace Superterran\Scanner\Tests;

use PHPUnit\Framework\TestCase;

class AppInstantiateTest extends TestCase
{
    /**
     * @return void
     */
    public function testInstantiates()
    {
        // phpcs:disable Magento2.Security.InsecureFunction.Found
        $output = shell_exec('php '. __DIR__ .'/../bin/scanner');
        // phpcs:enable
        $this->assertStringContainsString('Available commands', $output);
    }
}
