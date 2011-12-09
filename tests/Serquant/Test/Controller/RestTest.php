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

use Serquant\Controller\Rest;
use Serquant\Service\Exception\InvalidArgumentException;
use Serquant\DependencyInjection\ContainerBuilder;
use Serquant\Persistence\Doctrine;
use Serquant\Service\Crud;
use Serquant\Service\Result;

class MyRest extends Rest
{
    protected $serviceName = 'serviceLayer';
}

/**
 * Rest test class
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class RestTest extends \Doctrine\Tests\OrmTestCase
{
    private $request;

    private $response;

    private $rest;

    private $serializer;

    protected function setUp()
    {
        $this->request = new \Zend_Controller_Request_HttpTestCase();
        $this->response = new \Zend_Controller_Response_HttpTestCase();
        $this->rest = new MyRest($this->request, $this->response);

        $this->serializer =
            $this->getMockBuilder('Serquant\Converter\Serializer')
                 ->disableOriginalConstructor()
                 ->getMock();
    }

    public function testGetServiceThatIsMissing()
    {
        $container = new ContainerBuilder();
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $method = new \ReflectionMethod($this->rest, 'getService');
        $method->setAccessible(true);
        $this->setExpectedException('Serquant\Controller\Exception\RuntimeException');
        $method->invoke($this->rest);
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

        $method = new \ReflectionMethod($this->rest, 'getService');
        $method->setAccessible(true);
        $this->setExpectedException('Serquant\Controller\Exception\RuntimeException');
        $method->invoke($this->rest);
    }

    public function testGetServiceImplementingExpectedInterface()
    {
        $persister = new Doctrine($this->_getTestEntityManager());
        $serviceLayer = new Crud(null, $persister);

        $container = new ContainerBuilder();
        $container->set('serviceLayer', $serviceLayer);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $method = new \ReflectionMethod($this->rest, 'getService');
        $method->setAccessible(true);
        $this->assertSame($serviceLayer, $method->invoke($this->rest));
    }

    public function testGetSerializerThatIsMissing()
    {
        $container = new ContainerBuilder();
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $method = new \ReflectionMethod($this->rest, 'getSerializer');
        $method->setAccessible(true);
        $this->setExpectedException('Serquant\Controller\Exception\RuntimeException');
        $method->invoke($this->rest);
    }

    public function testGetSerializerNotImplementingExpectedInterface()
    {
        $serializer = new \stdClass();
        $container = new ContainerBuilder();
        $container->set('serializer', $serializer);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $method = new \ReflectionMethod($this->rest, 'getSerializer');
        $method->setAccessible(true);
        $this->setExpectedException('Serquant\Controller\Exception\RuntimeException');
        $method->invoke($this->rest);
    }

    public function testGetSerializerImplementingExpectedInterface()
    {
        $container = new ContainerBuilder();
        $container->set('serializer', $this->serializer);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $method = new \ReflectionMethod($this->rest, 'getSerializer');
        $method->setAccessible(true);
        $this->assertSame($this->serializer, $method->invoke($this->rest));
    }

    public function testSanitizeRql()
    {
        $method = new \ReflectionMethod($this->rest, 'sanitizeRql');
        $method->setAccessible(true);

        $query = array (
            'key1' => '1',
            'key2' => 2
        );
        $rql = $method->invoke($this->rest, $query);
        $diff = array_diff_assoc($query, $rql);
        $this->assertEmpty($diff, 'Key and value are both populated');

        $query = array (
            '1',
            2
        );
        $rql = $method->invoke($this->rest, $query);
        $diff = array_diff_assoc($query, $rql);
        $this->assertEmpty($diff, 'Only the value is populated');

        $query = array (
            'id' => 1,
            'limit(1,10)' => null,
            'select(id,name)' => ''
        );
        $rql = $method->invoke($this->rest, $query);
        $diff = array_diff_assoc(array('id' => 1, 'limit(1,10)', 'select(id,name)'), $rql);
        $this->assertEmpty($diff, 'Operator on the key side');
    }

    public function testIndexAction()
    {
        $service = $this->getMockBuilder('Serquant\Service\Crud')
                        ->disableOriginalConstructor()
                        ->getMock();
        $result = new Result(Result::STATUS_SUCCESS, null);
        $service->expects($this->any())
                ->method('fetchPage')
                ->will($this->returnValue($result));

        $container = new ContainerBuilder();
        $container->set('serviceLayer', $service);
        $container->set('serializer', $this->serializer);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $this->rest->view = new \Zend_View(array('basePath' => APPLICATION_ROOT));

        $this->rest->indexAction();
        $view = $this->rest->view;
        $this->assertTrue(isset($view->response));
        $this->assertSame($this->response, $view->response);
        $this->assertTrue(isset($view->result));
        $this->assertSame($result, $view->result);
        $this->assertTrue(isset($view->serializer));
        $this->assertSame($this->serializer, $view->serializer);
    }

    public function testGetActionWithoutId()
    {
        $service = $this->getMockBuilder('Serquant\Service\Crud')
                        ->disableOriginalConstructor()
                        ->getMock();
        $service->expects($this->any())
                ->method('retrieve')
                ->will($this->throwException(new InvalidArgumentException()));

        $container = new ContainerBuilder();
        $container->set('serviceLayer', $service);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $this->setExpectedException('Serquant\Service\Exception\InvalidArgumentException');
        $this->rest->getAction();
    }

    public function testGetActionWithExpectedId()
    {
        $service = $this->getMockBuilder('Serquant\Service\Crud')
                        ->disableOriginalConstructor()
                        ->getMock();
        $result = new Result(Result::STATUS_SUCCESS, null);
        $service->expects($this->any())
                ->method('retrieve')
                ->will($this->returnValue($result));

        $container = new ContainerBuilder();
        $container->set('serviceLayer', $service);
        $container->set('serializer', $this->serializer);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $this->request->setParam('id', 1);
        $this->rest->view = new \Zend_View(array('basePath' => APPLICATION_ROOT));

        $this->rest->getAction();
        $view = $this->rest->view;
        $this->assertTrue(isset($view->response));
        $this->assertSame($this->response, $view->response);
        $this->assertTrue(isset($view->result));
        $this->assertSame($result, $view->result);
        $this->assertTrue(isset($view->serializer));
        $this->assertSame($this->serializer, $view->serializer);
    }

    public function testPostAction()
    {
        $service = $this->getMockBuilder('Serquant\Service\Crud')
                        ->disableOriginalConstructor()
                        ->getMock();
        $result = new Result(Result::STATUS_SUCCESS, null);
        $service->expects($this->any())
                ->method('create')
                ->will($this->returnValue($result));

        $container = new ContainerBuilder();
        $container->set('serviceLayer', $service);
        $container->set('serializer', $this->serializer);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $this->request->setParam('id', 1);
        $this->rest->view = new \Zend_View(array('basePath' => APPLICATION_ROOT));
        $params = new \Serquant\Controller\Helper\Params();
        \Zend_Controller_Action_HelperBroker::addHelper($params);

        $this->rest->postAction();
        $view = $this->rest->view;
        $this->assertTrue(isset($view->response));
        $this->assertSame($this->response, $view->response);
        $this->assertTrue(isset($view->result));
        $this->assertSame($result, $view->result);
        $this->assertTrue(isset($view->serializer));
        $this->assertSame($this->serializer, $view->serializer);
    }

    public function testPutActionWithoutId()
    {
        $service = $this->getMockBuilder('Serquant\Service\Crud')
                        ->disableOriginalConstructor()
                        ->getMock();
        $service->expects($this->any())
                ->method('update')
                ->will($this->throwException(new InvalidArgumentException()));

        $container = new ContainerBuilder();
        $container->set('serviceLayer', $service);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $this->setExpectedException('Serquant\Service\Exception\InvalidArgumentException');
        $this->rest->putAction();
    }

    public function testPutActionWithExpectedId()
    {
        $service = $this->getMockBuilder('Serquant\Service\Crud')
                        ->disableOriginalConstructor()
                        ->getMock();
        $result = new Result(Result::STATUS_SUCCESS, null);
        $service->expects($this->any())
                ->method('update')
                ->will($this->returnValue($result));

        $container = new ContainerBuilder();
        $container->set('serviceLayer', $service);
        $container->set('serializer', $this->serializer);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $this->request->setParam('id', 1);
        $this->rest->view = new \Zend_View(array('basePath' => APPLICATION_ROOT));
        $params = new \Serquant\Controller\Helper\Params();
        \Zend_Controller_Action_HelperBroker::addHelper($params);

        $this->rest->putAction();
        $view = $this->rest->view;
        $this->assertTrue(isset($view->response));
        $this->assertSame($this->response, $view->response);
        $this->assertTrue(isset($view->result));
        $this->assertSame($result, $view->result);
        $this->assertTrue(isset($view->serializer));
        $this->assertSame($this->serializer, $view->serializer);
    }

    public function testDeleteActionWithoutId()
    {
        $service = $this->getMockBuilder('Serquant\Service\Crud')
                        ->disableOriginalConstructor()
                        ->getMock();
        $service->expects($this->any())
                ->method('delete')
                ->will($this->throwException(new InvalidArgumentException()));

        $container = new ContainerBuilder();
        $container->set('serviceLayer', $service);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $this->setExpectedException('Serquant\Service\Exception\InvalidArgumentException');
        $this->rest->deleteAction();
    }

    public function testDeleteActionWithExpectedId()
    {
        $service = $this->getMockBuilder('Serquant\Service\Crud')
                        ->disableOriginalConstructor()
                        ->getMock();
        $result = new Result(Result::STATUS_SUCCESS, null);
        $service->expects($this->any())
                ->method('delete')
                ->will($this->returnValue($result));

        $container = new ContainerBuilder();
        $container->set('serviceLayer', $service);
        $container->set('serializer', $this->serializer);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $this->request->setParam('id', 1);
        $this->rest->view = new \Zend_View(array('basePath' => APPLICATION_ROOT));
        $params = new \Serquant\Controller\Helper\Params();
        \Zend_Controller_Action_HelperBroker::addHelper($params);

        $this->rest->deleteAction();
        $view = $this->rest->view;
        $this->assertTrue(isset($view->response));
        $this->assertSame($this->response, $view->response);
        $this->assertTrue(isset($view->result));
        $this->assertSame($result, $view->result);
        $this->assertTrue(isset($view->serializer));
        $this->assertSame($this->serializer, $view->serializer);
    }

    public function testNewAction()
    {
        $service = $this->getMockBuilder('Serquant\Service\Crud')
                        ->disableOriginalConstructor()
                        ->getMock();
        $result = new Result(Result::STATUS_SUCCESS, null);
        $service->expects($this->any())
                ->method('getDefault')
                ->will($this->returnValue($result));

        $container = new ContainerBuilder();
        $container->set('serviceLayer', $service);
        $container->set('serializer', $this->serializer);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $this->rest->view = new \Zend_View(array('basePath' => APPLICATION_ROOT));

        $this->rest->newAction();
        $view = $this->rest->view;
        $this->assertTrue(isset($view->response));
        $this->assertSame($this->response, $view->response);
        $this->assertTrue(isset($view->result));
        $this->assertSame($result, $view->result);
        $this->assertTrue(isset($view->serializer));
        $this->assertSame($this->serializer, $view->serializer);
    }

    public function testPostDispatch()
    {
        $view = new \Zend_View(array('basePath' => APPLICATION_ROOT));
        $viewRenderer = new \Zend_Controller_Action_Helper_ViewRenderer(
            $view,
            array('noController' => false)
        );
        \Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
        $front = \Zend_Controller_Front::getInstance();
        $front->setControllerDirectory(APPLICATION_ROOT);

        $this->rest->postDispatch();
        $this->assertTrue($viewRenderer->getNoController());
    }
}