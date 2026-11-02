<?php
namespace DTS\eBaySDK\Test;

use DTS\eBaySDK\ConfigurationResolver;
use DTS\eBaySDK\Test\Mocks\StaticMethods;

class ConfigurationResolverTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaults()
    {
        $r = new ConfigurationResolver([
            'array' => [
                'valid' => ['array'],
                'default' => ['foo']
            ],
            'bool' => [
                'valid' => ['bool'],
                'default' => true
            ],
            'callable' => [
                'valid' => ['int'],
                'default' => [StaticMethods::class, 'defaultConfigValue']
            ],
            'int' => [
                'valid' => ['int'],
                'default' => -1
            ],
            'string' => [
                'valid' => ['string'],
                'default' => 'foo'
            ]
        ]);

        $this->assertEquals($r->resolve([]), [
            'array' => ['foo'],
            'bool' => true,
            'callable' => -1,
            'int' => -1,
            'string' => 'foo',
        ]);
    }

    public function testRequired()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required configuration options');

        $r = new ConfigurationResolver([
            'foo' => [
                'valid' => ['int'],
                'required' => true
            ]
        ]);
        $r->resolve([]);
    }

    public function testValidatesArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration value provided for "foo". Expected array, but got int(-1)');

        $r = new ConfigurationResolver([
            'foo' => [
                'valid' => ['array']
            ]
        ]);
        $r->resolve(['foo' => -1]);
    }

    public function testValidatesBool()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration value provided for "foo". Expected bool, but got int(-1)');

        $r = new ConfigurationResolver([
            'foo' => [
                'valid' => ['bool']
            ]
        ]);
        $r->resolve(['foo' => -1]);
    }

    public function testValidatesCallable()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration value provided for "foo". Expected callable, but got int(-1)');

        $r = new ConfigurationResolver([
            'foo' => [
                'valid' => ['callable']
            ]
        ]);
        $r->resolve(['foo' => -1]);
    }

    public function testValidatesInstanceOf()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration value provided for "foo". Expected StdClass, but got int(-1)');

        $r = new ConfigurationResolver([
            'foo' => [
                'valid' => ['StdClass']
            ]
        ]);
        $r->resolve(['foo' => -1]);
    }

    public function testValidatesInt()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration value provided for "foo". Expected int, but got string(3)');

        $r = new ConfigurationResolver([
            'foo' => [
                'valid' => ['int']
            ]
        ]);
        $r->resolve(['foo' => 'foo']);
    }

    public function testValidatesStrings()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration value provided for "foo". Expected string, but got int(-1)');

        $r = new ConfigurationResolver([
            'foo' => [
                'valid' => ['string']
            ]
        ]);
        $r->resolve(['foo' => -1]);
    }

    public function testAllowsValid()
    {
        $r = new ConfigurationResolver([
            'array' => [
                'valid' => ['array']
            ],
            'bool' => [
                'valid' => ['bool']
            ],
            'callable' => [
                'valid' => ['callable']
            ],
            'int' => [
                'valid' => ['int']
            ],
            'stdClass' => [
                'valid' => ['StdClass']
            ],
            'string' => [
                'valid' => ['string']
            ]
        ]);

        $options = [
            'array' => [],
            'bool' => true,
            'callable' => function () {
            },
            'int' => 1,
            'stdClass' => new \stdClass(),
            'string' => 'foo'
        ];

        $this->assertEquals($r->resolve($options), $options);
    }

    public function testFn()
    {
        $r = new ConfigurationResolver([
            'foo' => [
                'valid' => ['int'],
                'fn' => [StaticMethods::class, 'applyConfigValue']
            ]
        ]);
        $this->assertEquals($r->resolve(['foo' => 1]), ['foo' => 3]);
    }
}
