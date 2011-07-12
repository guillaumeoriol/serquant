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
namespace Serquant\Test\Persistence;

use Serquant\Persistence\Zend\Db\Table;

class ZendCreateTest extends \Serquant\Resource\Persistence\ZendTestCase
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

    public function testCreateSetupEntityId()
    {
        $row = $this->getMock('Zend_Db_Table_Row');
        $row->expects($this->any())
            ->method('save')
            ->will($this->returnValue(1));

        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('createRow')
              ->will($this->returnValue($row));

        $this->persister->setTableGateway($table);

        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->status = 'deprecated';
        $entity->username = 'gw';
        $entity->name = 'Washington';

        $this->persister->create($entity);
        $this->assertNotNull($entity->getId());

        $property = new \ReflectionProperty($this->persister, 'loadedEntities');
        $property->setAccessible(true);
        $loadedEntities = $property->getValue($this->persister);
        $this->assertTrue($loadedEntities->hasEntity($entity));
    }
}
