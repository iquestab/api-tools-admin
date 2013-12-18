<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Admin\Model;

use PHPUnit_Framework_TestCase as TestCase;
use ZF\Apigility\Admin\Model\InputFilterModel;
use ZF\Configuration\ResourceFactory as ConfigResourceFactory;
use ZF\Configuration\ModuleUtils;
use Zend\Config\Writer\PhpArray;

require_once __DIR__ . '/TestAsset/module/InputFilter/Module.php';

class InputFilterModelTest extends TestCase
{
    public function setUp()
    {
        $modules = array(
            'InputFilter' => new \InputFilter\Module()
        );


        $this->moduleManager = $this->getMockBuilder('Zend\ModuleManager\ModuleManager')
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->moduleManager->expects($this->any())
                            ->method('getLoadedModules')
                            ->will($this->returnValue($modules));

        $this->writer        = new PhpArray();
        $moduleUtils         = new ModuleUtils($this->moduleManager);
        $this->configFactory = new ConfigResourceFactory($moduleUtils, $this->writer);
        $this->model         = new InputFilterModel($this->configFactory);

        $this->basePath      = __DIR__ . '/TestAsset/module/InputFilter/config';
        $this->config        = include $this->basePath . '/module.config.php';

        copy($this->basePath . '/module.config.php', $this->basePath . '/module.config.php.old');
    }

    public function tearDown()
    {
        copy($this->basePath .'/module.config.php.old', $this->basePath . '/module.config.php');
        unlink($this->basePath . '/module.config.php.old');
    }

    public function testFetch()
    {
        $result = $this->model->fetch('InputFilter', 'InputFilter\V1\Rest\Foo\Controller');
        $this->assertInternalType('array', $result);
        $this->assertEquals(1, count($result));
        $inputFilter = array_shift($result);
        $this->assertInstanceOf('ZF\Apigility\Admin\Model\InputFilterEntity', $inputFilter);
        $this->assertEquals($this->config['input_filters']['InputFilter\V1\Rest\Foo\Validator']['foo'], $inputFilter['foo']);
    }

    public function testAddInputFilterExistingController()
    {
        $inputfilter = [
            'bar' => [
                'name' => 'bar',
                'validators' => [
                    [
                        'name' => 'NotEmpty'
                    ]
                ]
            ]
        ];
        $result = $this->model->update('InputFilter', 'InputFilter\V1\Rest\Foo\Controller', $inputfilter);
        $this->assertInstanceOf('ZF\Apigility\Admin\Model\InputFilterEntity', $result);
        $this->assertEquals($inputfilter['bar'], $result['bar'], sprintf("Updates: %s\n\nResult: %s\n", var_export($inputfilter, 1), var_export($result, 1)));
    }

    public function testAddInputFilterNewController()
    {
        $inputfilter = [
            'bar' => [
                'name' => 'bar',
                'validators' => [
                    [
                        'name' => 'NotEmpty'
                    ]
                ]
            ]
        ];

        // new controller
        $controller = 'InputFilter\V1\Rest\Bar\Controller';
        $result = $this->model->update('InputFilter', $controller, $inputfilter);
        $this->assertInstanceOf('ZF\Apigility\Admin\Model\InputFilterEntity', $result);
        $this->assertEquals($inputfilter['bar'], $result['bar']);

        $config = include $this->basePath . '/module.config.php';
        $this->assertEquals('InputFilter\V1\Rest\Bar\Validator', $config['zf-content-validation'][$controller]['input_filter']);
    }

    public function testRemoveInputFilter()
    {
        $this->assertTrue($this->model->remove('InputFilter', 'InputFilter\V1\Rest\Foo\Controller', 'InputFilter\V1\Rest\Foo\Validator'));
    }

    public function testModuleExists()
    {
        $this->assertTrue($this->model->moduleExists('InputFilter'));
    }

    public function testControllerExists()
    {
        $this->assertTrue($this->model->controllerExists('InputFilter', 'InputFilter\V1\Rest\Foo\Controller'));
    }
}
