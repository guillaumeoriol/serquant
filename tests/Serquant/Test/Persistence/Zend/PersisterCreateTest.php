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

class PersisterCreateTest extends \Serquant\Resource\Persistence\ZendTestCase
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
        $this->persister = new \Serquant\Persistence\Zend\Persister(array(), $evm);
    }

    /**
     * @covers Serquant\Persistence\Zend\Persister::create
     */
    public function testCreate()
    {
        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $personGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';
        $this->persister->setTableGateway($personEntityClass, $personGatewayClass);

        $entity = new $personEntityClass;
        $entity->setFirstName('Charles');
        $entity->setLastName('de Gaulle');
        $this->assertNull($entity->getId());

        $this->persister->create($entity);
        $this->assertNotNull($entity->getId());
    }
}
