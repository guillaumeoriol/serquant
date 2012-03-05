<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Test
 * @author   Baptiste Tripot <bt@technema.fr>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Test\Paginator;

use Serquant\Persistence\Zend\Configuration;
use Serquant\Paginator\Paginator;
use Serquant\Paginator\Adapter\DbSelect;
use Serquant\Type\Exception;

/**
 * Test class for Paginator
 *
 * @category Serquant
 * @package  Test
 * @author   Baptiste Tripot <bt@technema.fr>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class PaginatorTest extends \Serquant\Resource\Persistence\ZendTestCase
{

    private $db;
    private $persister;

    private function setupDatabase()
    {
        $dataSets = array();

        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/people.yaml'
        );

        $data = new \PHPUnit_Extensions_Database_DataSet_CompositeDataSet(
            $dataSets
        );

        $this->db = $this->getTestAdapter();
        $connection = new \Zend_Test_PHPUnit_Db_Connection($this->db, null);
        $tester = new \Zend_Test_PHPUnit_Db_SimpleTester($connection);
        $tester->setupDatabase($data);
    }

    protected function setUp()
    {
        $this->setupDatabase();
        $evm = new \Doctrine\Common\EventManager();
        $this->config = new Configuration();
        $this->config->setEventManager($evm);
        $this->persister = new \Serquant\Persistence\Zend\Persister($this->config);
    }

    /**
     * @covers Serquant\Paginator\Paginator::getItemOffset
     */
    public function testGetItemOffset()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $this->persister->setTableGateway($entityName, $gateway);

        $adapter = new DbSelect($gateway->select(), $this->persister, $entityName);

        $paginator = new Paginator($adapter);

        $item = $paginator->getItemOffset();

        $this->assertEquals(NULL, $item);
    }

	/**
     * @covers Serquant\Paginator\Paginator::setItemOffset
     * @depends testGetItemOffset
     */
    public function testSetItemOffset()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $this->persister->setTableGateway($entityName, $gateway);

        $adapter = new DbSelect($gateway->select(), $this->persister, $entityName);

        $paginator = new Paginator($adapter);

        $paginator->setItemOffset(55);

        $this->assertEquals(55, $paginator->getItemOffset());
    }

    /**
     * @covers Serquant\Paginator\Paginator::getCurrentItems
     */
    public function testCanGetCurrentItemsWithTraversable()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $this->persister->setTableGateway($entityName, $gateway);

        // Create a stub for the SomeClass class.
        $stub = $this->getMockBuilder('Serquant\Paginator\Adapter\DbSelect')
                     ->disableOriginalConstructor()
                     ->getMock();

        // Configure the stub.
        $stub->expects($this->any())
             ->method('getItems')
             ->will($this->returnValue(new \ArrayIterator()));

        $paginator = new Paginator($stub);
        $items = $paginator->getCurrentItems();
        $this->assertInstanceOf('Traversable', $items);
    }

	/**
     * @covers Serquant\Paginator\Paginator::getCurrentItems
     */
    public function testCanGetCurrentItemsWithArray()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $this->persister->setTableGateway($entityName, $gateway);

        // Create a stub for the SomeClass class.
        $stub = $this->getMockBuilder('Serquant\Paginator\Adapter\DbSelect')
                     ->setConstructorArgs(array($gateway->select(), $this->persister, $entityName))
                     ->getMock();

        // Configure the stub.
        $stub->expects($this->any())
             ->method('getItems')
             ->will($this->returnValue(array()));

        $paginator = new Paginator($stub);
        $items = $paginator->getCurrentItems();
        $this->assertInstanceOf('Traversable', $items);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testEnablingCacheThrowsException()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $this->persister->setTableGateway($entityName, $gateway);

        $adapter = new DbSelect($gateway->select(), $this->persister, $entityName);

        $paginator = new Paginator($adapter);
        $items = $paginator->setCache(new \Zend_Cache_Core());
    }

	/**
     * @expectedException RuntimeException
     */
    public function testEnablingFilterThrowsException()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $this->persister->setTableGateway($entityName, $gateway);

        $adapter = new DbSelect($gateway->select(), $this->persister, $entityName);

        $paginator = new Paginator($adapter);
        $items = $paginator->setFilter();
    }
}