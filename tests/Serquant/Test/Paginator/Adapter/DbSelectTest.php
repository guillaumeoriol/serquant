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
namespace Serquant\Test\Paginator\Adapter;

use Serquant\Paginator\Adapter\DbSelect;
use Serquant\Persistence\Zend\Configuration;

/**
 * Test class for DbSelect
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class DbSelectTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;
    private $persister;

    private function setupDatabase()
    {
        $dataSets = array();

        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            realpath(dirname(__FILE__) . '/../fixture/people.yaml')
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
        $config = new Configuration();
        $config->setEventManager($evm);
        $this->persister = new \Serquant\Persistence\Zend\Persister($config);
    }

    /**
     * @covers Serquant\Paginator\Adapter\DbSelect::getItems
     */
    public function testGetItems()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $this->persister->setTableGateway($entityName, $gateway);

        $adapter = new DbSelect($gateway->select(), $this->persister, $entityName);
        $entities = $adapter->getItems(1, 5);
        $this->assertInternalType('array', $entities);
    }
}