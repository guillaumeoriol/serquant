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

use Serquant\Controller\Helper\Params;


class NonHttpRequest extends \Zend_Controller_Request_Simple
{
    public function getRawBody()
    {
        return '{id:1}';
    }

    public function getHeader($header)
    {
        return 'application/json';
    }
}

/**
 * Params test class.
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ParamsTest extends \PHPUnit_Framework_TestCase
{
    public function testInitWithUrlEncodedBody()
    {
        $expected = array('id' => '1', 'foo' => 'bar');
        $stub = $this->getMock('Zend_Controller_Request_Http');
        $stub->expects($this->any())
             ->method('getRawBody')
             ->will($this->returnValue('id=1&foo=bar'));
        $stub->expects($this->any())
             ->method('getHeader')
             ->will($this->returnValue('application/x-www-form-urlencoded'));

        $controller = \Zend_Controller_Front::getInstance();
        $controller->setRequest($stub);
        $params = new Params();
        $params->init();
        $this->assertEquals($expected, $params->getBodyParams());
    }

    public function testInitWithXmlBody()
    {
        $expected = array('id' => 1, 'foo' => 'bar');
        $stub = $this->getMock('Zend_Controller_Request_Http');
        $stub->expects($this->any())
             ->method('getRawBody')
             ->will($this->returnValue('<?xml version="1.0"?><data><id>1</id><foo>bar</foo></data>'));
        $stub->expects($this->any())
             ->method('getHeader')
             ->will($this->returnValue('application/xml'));

        $controller = \Zend_Controller_Front::getInstance();
        $controller->setRequest($stub);
        $params = new Params();
        $params->init();
        $this->assertEquals($expected, $params->getBodyParams());
    }

    public function testInitWithJsonBody()
    {
        $expected = array('id' => 1, 'foo' => 'bar');
        $stub = $this->getMock('Zend_Controller_Request_Http');
        $stub->expects($this->any())
             ->method('getRawBody')
             ->will($this->returnValue(json_encode($expected)));
        $stub->expects($this->any())
             ->method('getHeader')
             ->will($this->returnValue('application/json'));

        $controller = \Zend_Controller_Front::getInstance();
        $controller->setRequest($stub);
        $params = new Params();
        $params->init();
        $this->assertEquals($expected, $params->getBodyParams());
    }

    public function testInitWithoutBody()
    {
        $stub = $this->getMock('Zend_Controller_Request_Http');
        $stub->expects($this->any())
             ->method('getRawBody')
             ->will($this->returnValue(false));

        $controller = \Zend_Controller_Front::getInstance();
        $controller->setRequest($stub);
        $params = new Params();
        $params->init();
        $this->assertEmpty($params->getBodyParams());
    }

    public function testInitWithNonHttpRequest()
    {
        $controller = \Zend_Controller_Front::getInstance();
        $controller->setRequest(new NonHttpRequest());
        $params = new Params();
        $params->init();

        // As NonHttpRequest#getRawBody always returns a JSON value {id:1} and
        // #getHeader returns 'application/json', getBodyParams() would return
        // array('id' => 1)
        $this->assertEmpty($params->getBodyParams());
    }

    public function testGetBodyParams()
    {
        $expected = array('a' => 1, 'b' => true, 'c' => 'foo');
        $params = new Params();
        $params->setBodyParams($expected);
        $this->assertEquals($expected, $params->getBodyParams());
    }

    public function testHasBodyParams()
    {
        $expected = array('a' => 1, 'b' => true, 'c' => 'foo');
        $params = new Params();
        $this->assertFalse($params->hasBodyParams());
        $params->setBodyParams($expected);
        $this->assertTrue($params->hasBodyParams());
    }

    public function testHasBodyParam()
    {
        $expected = array('a' => 1, 'b' => true, 'c' => 'foo');
        $params = new Params();
        $params->setBodyParams($expected);
        $this->assertTrue($params->hasBodyParam('a'));
        $this->assertTrue($params->hasBodyParam('b'));
        $this->assertTrue($params->hasBodyParam('c'));
        $this->assertFalse($params->hasBodyParam('d'));
    }

    public function testGetBodyParam()
    {
        $expected = array('a' => 1, 'b' => true, 'c' => 'foo');
        $params = new Params();
        $params->setBodyParams($expected);
        $this->assertEquals(1, $params->getBodyParam('a'));
        $this->assertEquals(true, $params->getBodyParam('b'));
        $this->assertEquals('foo', $params->getBodyParam('c'));
        $this->assertNull($params->getBodyParam('d'));
    }

    public function testGetSubmitParams()
    {
        $expected = array('a' => 1, 'b' => true, 'c' => 'foo');
        $params = new Params();
        $params->setBodyParams($expected);
        $this->assertEquals($expected, $params->getSubmitParams());
    }

    public function testGetSubmitParamsWithoutBody()
    {
        $expected = 'dummy';

        $stub = $this->getMock('Zend_Controller_Request_Http');
        $stub->expects($this->any())
             ->method('getPost')
             ->will($this->returnValue($expected));

        $controller = \Zend_Controller_Front::getInstance();
        $controller->setRequest($stub);
        $params = new Params();
        $this->assertEquals($expected, $params->getSubmitParams());
    }

    public function testDirect()
    {
        $expected = array('a' => 1, 'b' => true, 'c' => 'foo');
        $params = new Params();
        $params->setBodyParams($expected);
        $this->assertEquals($expected, $params->direct());
    }
}
