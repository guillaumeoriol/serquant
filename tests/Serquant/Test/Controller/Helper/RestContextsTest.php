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
namespace Serquant\Test\Controller\Helper;

use Serquant\Controller\Helper\RestContexts;

class MyNonRestController extends \Zend_Controller_Action
{
    public function indexAction()
    {
    }
}

class MyRestController extends \Serquant\Controller\Rest
{
    public function indexAction()
    {
    }
}

/**
 * RestContexts test class
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class RestContextsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * To test this function in case of a NON REST controller, we enable the
     * auto JSON serialization at the beginning and check that it still enabled
     * at the end, as this feature is disabled when a REST controllers is used.
     */
    public function testPreDispatchWithNonRestController()
    {
        $request = new \Zend_Controller_Request_Http();
        $response = new \Zend_Controller_Response_Http();
        $controller = new MyNonRestController($request, $response);

        $contextSwitch = $controller->getHelper('contextSwitch');
        $contextSwitch->setAutoJsonSerialization(true);

        $restContexts = new RestContexts();
        $restContexts->setActionController($controller);
        $restContexts->preDispatch();
        $this->assertTrue($contextSwitch->getAutoJsonSerialization());

        \Zend_Controller_Action_HelperBroker::resetHelpers();
    }

    /**
     * To test this function in case of a REST controller, we enable the auto
     * JSON serialization at the beginning and check that it is disabled at the
     * end, as this feature is disabled when a REST controllers is used.
     */
    public function testPreDispatchWithRestController()
    {
        $request = new \Zend_Controller_Request_Http();
        $response = new \Zend_Controller_Response_Http();
        $response = $this->getMock('Zend_Controller_Response_Http');
        $response->expects($this->any())
                 ->method('setHeader');
        $controller = new MyRestController($request, $response);

        $contextSwitch = $controller->getHelper('contextSwitch');
        $contextSwitch->setAutoJsonSerialization(true);

        $restContexts = new RestContexts();
        $restContexts->setActionController($controller);

        $restContexts->preDispatch();
        $this->assertFalse($contextSwitch->getAutoJsonSerialization());

        \Zend_Controller_Action_HelperBroker::resetHelpers();
    }
}