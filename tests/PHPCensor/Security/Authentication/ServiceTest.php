<?php

/**
 * PHPCI - Continuous Integration for PHP
 *
 * @copyright    Copyright 2014, Block 8 Limited.
 * @license      https://github.com/Block8/PHPCI/blob/master/LICENSE.md
 * @link         https://www.phptesting.org/
 */

namespace Tests\PHPCensor\Security\Authentication;

use PHPCensor\Security\Authentication\Service;

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Service::getInstance
     */
    public function testGetInstance()
    {
        $this->assertInstanceOf('\PHPCensor\Security\Authentication\Service', Service::getInstance());
    }

    /**
     * @covers Service::buildProvider
     */
    public function testBuildBuiltinProvider()
    {
        $provider = Service::buildProvider('test', ['type' => 'internal']);

        $this->assertInstanceOf('\PHPCensor\Security\Authentication\UserProvider\Internal', $provider);
    }

    /**
     * @covers Service::buildProvider
     */
    public function testBuildAnyProvider()
    {
        $config   = ['type' => '\Tests\PHPCensor\Security\Authentication\DummyProvider'];
        $provider = Service::buildProvider("test", $config);

        $this->assertInstanceOf('\Tests\PHPCensor\Security\Authentication\DummyProvider', $provider);
        $this->assertEquals('test', $provider->key);
        $this->assertEquals($config, $provider->config);
    }

    /**
     * @covers Service::getProviders
     */
    public function testGetProviders()
    {
        $a         = $this->prophesize('\PHPCensor\Security\Authentication\UserProvider')->reveal();
        $b         = $this->prophesize('\PHPCensor\Security\Authentication\UserProvider')->reveal();
        $providers = ['a' => $a, 'b' => $b];

        $service = new Service($providers);

        $this->assertEquals($providers, $service->getProviders());
    }

    /**
     * @covers Service::getLoginPasswordProviders
     */
    public function testGetLoginPasswordProviders()
    {
        $a         = $this->prophesize('\PHPCensor\Security\Authentication\UserProvider')->reveal();
        $b         = $this->prophesize('\PHPCensor\Security\Authentication\LoginPasswordProvider')->reveal();
        $providers = ['a' => $a, 'b' => $b];

        $service = new Service($providers);

        $this->assertEquals(['b' => $b], $service->getLoginPasswordProviders());
    }
}

class DummyProvider
{
    public $key;
    public $config;
    public function __construct($key, array $config)
    {
        $this->key = $key;
        $this->config = $config;
    }
}
