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
namespace Serquant\Test\DependencyInjection\Factory;

use Serquant\DependencyInjection\Factory\ZendPersisterFactory;

/**
 * Test class for the ZendPersisterFactory
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ZendPersisterFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetWithEmptyConfig()
    {
        $config = array();
        $this->setExpectedException('Serquant\Persistence\Exception\InvalidArgumentException');
        $persister = ZendPersisterFactory::get($config);
    }

    public function testConstructWithEventManager()
    {
        $evm = new \Doctrine\Common\EventManager();
        $config = array(
            'eventManager' => $evm
        );
        $persister = ZendPersisterFactory::get($config);
        $this->assertInstanceOf('Serquant\Persistence\Zend\Persister', $persister);

        $prop = new \ReflectionProperty($persister, 'eventManager');
        $prop->setAccessible(true);
        $actual = $prop->getValue($persister);
        $this->assertSame($evm, $actual);
    }

    public function testConstructWithGatewayMap()
    {
        $gatewayMap = array('Serquant\Resource\Persistence\Zend\Person' => 'Serquant\Resource\Persistence\Zend\Db\Table\Person');
        $evm = new \Doctrine\Common\EventManager();
        $config = array(
            'eventManager' => $evm,
            'gatewayMap' => $gatewayMap
        );
        $persister = ZendPersisterFactory::get($config);
        $this->assertInstanceOf('Serquant\Persistence\Zend\Persister', $persister);

        $prop = new \ReflectionProperty($persister, 'gatewayMap');
        $prop->setAccessible(true);
        $actual = $prop->getValue($persister);
        $this->assertSame($gatewayMap, $actual);
    }

    public function testConstructWithProxyNamespace()
    {
        $proxyNamespace = 'Serquant\Resource\Persistence\Zend\Proxy';
        $evm = new \Doctrine\Common\EventManager();
        $config = array(
            'eventManager' => $evm,
            'proxyNamespace' => $proxyNamespace
        );
        $persister = ZendPersisterFactory::get($config);
        $this->assertInstanceOf('Serquant\Persistence\Zend\Persister', $persister);
        $this->assertEquals($proxyNamespace, $persister->getProxyNamespace());
    }
}