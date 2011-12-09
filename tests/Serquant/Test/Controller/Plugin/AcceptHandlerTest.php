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
namespace Serquant\Test\Controller\Plugin;

use Serquant\Controller\Plugin\AcceptHandler;

/**
 * AcceptHandler test class
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class AcceptHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testDispatchLoopStartupWithNonHttpRequest()
    {
        $request = $this->getMock('Zend_Controller_Request_Simple');
        $request->expects($this->any())
                ->method('getHeader')
                ->will($this->throwException(new \RuntimeException()));

        $plugin = new AcceptHandler();
        try {
            $plugin->dispatchLoopStartup($request);
        } catch (\RuntimeException $e) {
            $this->fail('An unexpected exception has been raised.');
        }
    }

    public function testDispatchLoopStartupWithHttpRequestHavingHtmlAccept()
    {
        $accept = 'text/html';
        $_SERVER['HTTP_ACCEPT'] = $accept;
        $request = new \Zend_Controller_Request_Http();

        $plugin = new AcceptHandler();
        $plugin->dispatchLoopStartup($request);
        $this->assertNull($request->getParam(AcceptHandler::CONTEXT_PARAM));
        unset($_SERVER['HTTP_ACCEPT']);
    }

    public function testDispatchLoopStartupWithHttpRequestHavingJsonAccept()
    {
        $accept = 'application/json';
        $_SERVER['HTTP_ACCEPT'] = $accept;
        $request = new \Zend_Controller_Request_Http();

        $plugin = new AcceptHandler();
        $plugin->dispatchLoopStartup($request);
        $this->assertEquals('json', $request->getParam(AcceptHandler::CONTEXT_PARAM));
        unset($_SERVER['HTTP_ACCEPT']);
    }

    public function testDispatchLoopStartupWithHttpRequestHavingXmlAcceptOnly()
    {
        $accept = 'application/xml';
        $_SERVER['HTTP_ACCEPT'] = $accept;
        $request = new \Zend_Controller_Request_Http();

        $plugin = new AcceptHandler();
        $plugin->dispatchLoopStartup($request);
        $this->assertEquals('xml', $request->getParam(AcceptHandler::CONTEXT_PARAM));
        unset($_SERVER['HTTP_ACCEPT']);
    }

    public function testDispatchLoopStartupWithHttpRequestHavingXmlOrHtmlAccept()
    {
        $accept = 'text/html,application/xml';
        $_SERVER['HTTP_ACCEPT'] = $accept;
        $request = new \Zend_Controller_Request_Http();

        $plugin = new AcceptHandler();
        $plugin->dispatchLoopStartup($request);
        $this->assertNull($request->getParam(AcceptHandler::CONTEXT_PARAM));
        unset($_SERVER['HTTP_ACCEPT']);
    }
}