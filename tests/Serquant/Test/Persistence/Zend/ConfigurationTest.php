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
namespace Serquant\Test\Persistence\Zend;

use Serquant\Persistence\Zend\Configuration;

/**
 * Test class for Configuration
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Serquant\Persistence\Zend\Configuration::getGatewayMap
     */
    public function testGetGatewayMap()
    {
        $config = new Configuration();
        $this->assertEmpty($config->getGatewayMap());
    }

    /**
     * @covers Serquant\Persistence\Zend\Configuration::setGatewayMap
     */
    public function testSetGatewayMap()
    {
        $gatewayMap = array('Serquant\Resource\Persistence\Zend\Person' => 'Serquant\Resource\Persistence\Zend\Db\Table\Person');
        $config = new Configuration();
        $config->setGatewayMap($gatewayMap);
        $this->assertSame($gatewayMap, $config->getGatewayMap());
    }

    /**
     * @covers Serquant\Persistence\Zend\Configuration::getEventManager
     */
    public function testGetEventManager()
    {
        $config = new Configuration();
        // No default value for the event manager
        $this->assertNull($config->getEventManager());
    }

    /**
     * @covers Serquant\Persistence\Zend\Configuration::setEventManager
     */
    public function testSetEventManager()
    {
        $eventManager = new \Doctrine\Common\EventManager();
        $config = new Configuration();
        $config->setEventManager($eventManager);
        $this->assertSame($eventManager, $config->getEventManager());
    }

    /**
     * @covers Serquant\Persistence\Zend\Configuration::getProxyNamespace
     */
    public function testGetProxyNamespace()
    {
        $config = new Configuration();
        $this->assertEmpty($config->getProxyNamespace());
    }

    /**
     * @covers Serquant\Persistence\Zend\Configuration::setProxyNamespace
     */
    public function testSetProxyNamespace()
    {
        $proxyNamespace = 'Serquant\Resource\Persistence\Zend\Proxy';
        $config = new Configuration();
        $config->setProxyNamespace($proxyNamespace);
        $this->assertSame($proxyNamespace, $config->getProxyNamespace());
    }

    /**
     * @covers Serquant\Persistence\Zend\Configuration::setProxyNamespace
     */
    public function testSetProxyNamespaceWithLeadingBackslash()
    {
        $proxyNamespace = '\Serquant\Resource\Persistence\Zend\Proxy';
        $config = new Configuration();
        $this->setExpectedException('Serquant\Persistence\Exception\InvalidArgumentException');
        $config->setProxyNamespace($proxyNamespace);
    }

    /**
     * @covers Serquant\Persistence\Zend\Configuration::setProxyNamespace
     */
    public function testSetProxyNamespaceWithTrailingBackslash()
    {
        $proxyNamespace = 'Serquant\Resource\Persistence\Zend\Proxy\\';
        $config = new Configuration();
        $this->setExpectedException('Serquant\Persistence\Exception\InvalidArgumentException');
        $config->setProxyNamespace($proxyNamespace);
    }
}
