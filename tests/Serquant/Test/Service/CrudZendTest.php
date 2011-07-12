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

    protected function setUp()
    {
        $this->db = $this->getTestAdapter();
        $this->em = $this->getTestEntityManager();
    }


    public function testFetchPairsWithExistingSelectOperator()
    {
        $this->setExpectedException('InvalidArgumentException');

        $entityName = $inputFilterName = null;
        $persister = new Zend($this->em);
        $service = new Crud($entityName, $inputFilterName, $persister);
        $service->fetchPairs('id', 'name', array('select(id,name)'));
    }

    public function testFetchPairsWithZendPersister()
    {
        $entityName = '\Serquant\Resource\Persistence\Zend\Role';
        $inputFilterName = null;

        $persister = new Zend($this->em);
        \Zend_Db_Table::setDefaultAdapter($this->db);

        $service = new Crud($entityName, $inputFilterName, $persister);
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
}
