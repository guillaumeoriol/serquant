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

use Serquant\Persistence\Doctrine,
    Serquant\Service\Crud;

class CrudDoctrineTest extends \Serquant\Resource\Persistence\OrmFunctionalTestCase
{
    private $em;

    private $persister;

    protected function setUp()
    {
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

    public function testFetchPairsWithZendPersister()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\Role';

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
}
