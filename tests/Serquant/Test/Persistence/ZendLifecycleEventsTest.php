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

use Serquant\Event\LifecycleEventArgs;

/**
 * Test class on lifecycle events in Zend persister.
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ZendLifecycleEventsTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    protected $db;
    protected $evm;
    protected $persister;

    private function setupDatabase()
    {
        $dataSets = array();

        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/people.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/issues.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cars.yaml'
        );

        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_accounts.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_users.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_addresses.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_phonenumbers.yaml'
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
//        $this->setupDatabase();
        $this->evm = new \Doctrine\Common\EventManager();
        $this->persister = new \Serquant\Persistence\Zend(array(), $this->evm);
    }

    public function testCreateWithPrePersistListener()
    {
        $listener = new PrePersistListener();
        $this->evm->addEventListener(PrePersistListener::prePersist, $listener);

        $gatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Role';
        $entityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $entity = new $entityClass;
        $entity->setName('guest');
        $this->persister->setTableGateway($entityClass, $gatewayClass);

        $this->assertFalse($listener->prePersistInvoked);
        $this->persister->create($entity);
        // Check the listener was invoked
        $this->assertTrue($listener->prePersistInvoked);
        // Check it was invoked BEFORE the entity was inserted in the database
        // and the id was generated
        $this->assertTrue($listener->idWasNullAtPrePersistStage);
        // And be sure the id was null for the right reason
        $this->assertNotNull($entity->getId());
    }
}

class PrePersistListener
{
    const prePersist = 'prePersist';

    public $prePersistInvoked = false;

    public $idWasNullAtPrePersistStage = null;

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->prePersistInvoked = true;

        $entity = $args->getEntity();
        $id = $entity->getId();
        if ($id === null) {
            $this->idWasNullAtPrePersistStage = true;
        }
    }
}