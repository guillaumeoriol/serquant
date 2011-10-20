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

use Serquant\Persistence\Zend,
    Serquant\Service\Crud;

class CrudZendTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;

    private $em;

    private $persister;

    protected function setUp()
    {
        $this->db = $this->getTestAdapter();
        \Zend_Db_Table::setDefaultAdapter($this->db);
        $this->em = $this->getTestEntityManager();
        $this->persister = new \Serquant\Persistence\Zend($this->em);
    }

    public function testFetchPairsWithExistingSelectOperator()
    {
        $this->setExpectedException('InvalidArgumentException');

        $entityName = null;
        $service = new Crud($entityName, $this->persister);
        $service->fetchPairs('id', 'name', array('select(id,name)'));
    }

    public function testFetchPairsWithZendPersister()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Role';

        $service = new Crud($entityName, $this->persister);
        $result = $service->fetchPairs('id', 'name', array());
        $data = $result->getData();

        $this->assertInternalType('array', $data);

        $this->assertEquals(3, count($data));

        $scalar = true;
        foreach ($data as $key => $value) {
            $scalar = $scalar && is_scalar($value);
        }
        $this->assertTrue($scalar);
    }

    public function testFetchPairsOnDifferentServicesWithSameZendPersister()
    {
        $entityName1 = 'Serquant\Resource\Persistence\Zend\Role';
        $entityName2 = 'Serquant\Resource\Persistence\Zend\Message';

        $service1 = new Crud($entityName1, $this->persister);
        $result1 = $service1->fetchPairs('id', 'name', array());
        $data1 = $result1->getData();

        $service2 = new Crud($entityName2, $this->persister);
        $result2 = $service2->fetchPairs('language', 'message', array());
        $data2 = $result2->getData();

        $this->assertNotEquals($result1, $result2);
    }
}
