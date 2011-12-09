<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Test\Controller;

use Serquant\Controller\Action;
use Serquant\DependencyInjection\ContainerBuilder;

class MyActionController extends Action
{
    protected $serviceName = 'serviceLayer';
}

/**
 * Action test class
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ActionTest extends \PHPUnit_Framework_TestCase
{
    private $controller;

    protected function setUp()
    {
        $this->request = new \Zend_Controller_Request_HttpTestCase();
        $this->response = new \Zend_Controller_Response_HttpTestCase();
        $this->controller = new MyActionController($this->request, $this->response);
    }

    public function testGetServiceThatIsMissing()
    {
        $container = new ContainerBuilder();
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $method = new \ReflectionMethod('Serquant\Controller\Action', 'getService');
        $method->setAccessible(true);
        $this->setExpectedException('Serquant\Controller\Exception\RuntimeException');
        $method->invoke($this->controller);
    }

    public function testGetServiceNotImplementingExpectedInterface()
    {
        $serviceLayer = new \stdClass();
        $container = new ContainerBuilder();
        $container->set('serviceLayer', $serviceLayer);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $method = new \ReflectionMethod('Serquant\Controller\Action', 'getService');
        $method->setAccessible(true);
        $this->setExpectedException('Serquant\Controller\Exception\RuntimeException');
        $method->invoke($this->controller);
    }

    public function testGetServiceImplementingExpectedInterface()
    {
        $serviceLayer = $this->getMockBuilder('Serquant\Service\Crud')
                             ->disableOriginalConstructor()
                             ->getMock();

        $container = new ContainerBuilder();
        $container->set('serviceLayer', $serviceLayer);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $method = new \ReflectionMethod($this->controller, 'getService');
        $method->setAccessible(true);
        $this->assertSame($serviceLayer, $method->invoke($this->controller));
    }
}