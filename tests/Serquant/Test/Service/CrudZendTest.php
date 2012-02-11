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
namespace Serquant\Test\Service;

use Serquant\Persistence\Zend;
use Serquant\Service\Crud;

class CrudZendTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;
    private $em;
    private $persister;

    private function setupDatabase()
    {
        $dataSets = array();
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/users.yaml'
        );
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
        $this->em = $this->getTestEntityManager();
        $this->persister = new \Serquant\Persistence\Zend($this->em);
    }

    /**
     * @group Zend
     */
    public function testFetchPairsWithExistingSelectOperator()
    {
        $this->setExpectedException('InvalidArgumentException');

        $entityName = null;
        $service = new Crud($entityName, $this->persister);
        $service->fetchPairs('id', 'name', array('select(id,name)'));
    }

    /**
     * @group Zend
     */
    public function testFetchPairsWithZendPersister()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';

        $service = new Crud($entityName, $this->persister);
        $result = $service->fetchPairs('id', 'name', array());
        $data = $result->getData();

        $this->assertInternalType('array', $data);

        $this->assertEquals(4, count($data));

        $scalar = true;
        foreach ($data as $key => $value) {
            $scalar = $scalar && is_scalar($value);
        }
        $this->assertTrue($scalar);
    }

    /**
     * @group issue-6
     * @group Zend
     */
    public function testFetchPairsOnDifferentServicesWithSameZendPersister()
    {
        $entityName1 = 'Serquant\Resource\Persistence\Zend\User';
        $entityName2 = 'Serquant\Resource\Persistence\Zend\Person';

        $service1 = new Crud($entityName1, $this->persister);
        $result1 = $service1->fetchPairs('id', 'name', array());
        $data1 = $result1->getData();

        $service2 = new Crud($entityName2, $this->persister);
        $result2 = $service2->fetchPairs('id', 'lastName', array());
        $data2 = $result2->getData();

        $this->assertNotEquals($result1, $result2);
    }
}
