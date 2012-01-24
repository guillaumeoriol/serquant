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

use Serquant\Persistence\Doctrine;
use Serquant\Service\Crud;

class CrudDoctrineTest extends \Serquant\Resource\Persistence\OrmFunctionalTestCase
{
    private $db;
    private $em;
    private $persister;

    private function setupDatabase()
    {
        $dataSet = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_accounts.yaml'
        );

        $this->db = $this->getTestAdapter();
        $connection = new \Zend_Test_PHPUnit_Db_Connection($this->db, null);
        $tester = new \Zend_Test_PHPUnit_Db_SimpleTester($connection);
        $tester->setupDatabase($dataSet);
    }

    protected function setUp()
    {
        $this->setupDatabase();
        $this->em = $this->getTestEntityManager();
        $this->persister = new Doctrine($this->em);
    }

    public function testFetchPairsWithExistingSelectOperator()
    {
        $this->setExpectedException('InvalidArgumentException');

        $entityName = null;
        $service = new Crud($entityName, $this->persister);
        $service->fetchPairs('id', 'name', array('select(id,name)'));
    }

    public function testFetchPairsWithDoctrinePersister()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount';

        $service = new Crud($entityName, $this->persister);
        $result = $service->fetchPairs('id', 'bank', array());
        $data = $result->getData();

        $this->assertInternalType('array', $data);

        $this->assertEquals(5, count($data));

        $scalar = true;
        foreach ($data as $key => $value) {
            $scalar = $scalar && is_scalar($value);
        }
        $this->assertTrue($scalar);
    }
}
