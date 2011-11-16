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

class RestTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $request = new \Zend_Controller_Request_HttpTestCase();
        $response = new \Zend_Controller_Response_HttpTestCase();
        $this->rest = new Rest($request, $response);
    }

    public function testGetSerializerMissing()
    {
        $container = new \Serquant\DependencyInjection\ContainerBuilder();
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $method = new \ReflectionMethod($this->rest, 'getSerializer');
        $method->setAccessible(true);
        $this->setExpectedException('Serquant\Controller\Exception\RuntimeException');
        $serializer = $method->invoke($this->rest);
    }

    public function testGetSerializerNotImplementingExpectedInterface()
    {
        $serializer = new \stdClass();
        $container = new \Serquant\DependencyInjection\ContainerBuilder();
        $container->set('serializer', $serializer);
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());

        $method = new \ReflectionMethod($this->rest, 'getSerializer');
        $method->setAccessible(true);
        $this->setExpectedException('Serquant\Controller\Exception\RuntimeException');
        $serializer = $method->invoke($this->rest);
    }

    public function testGetRql()
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
}