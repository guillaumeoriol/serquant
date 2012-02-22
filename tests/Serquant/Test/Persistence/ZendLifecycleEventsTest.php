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

use Doctrine\Common\EventManager;
use Serquant\Event\LifecycleEvent;
use Serquant\Event\LifecycleEventArgs;
use Serquant\Event\PreUpdateLifecycleEventArgs;

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
            dirname(__FILE__) . '/fixture/roles.yaml'
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
        $this->evm = new \Doctrine\Common\EventManager();
        $this->persister = new \Serquant\Persistence\Zend(array(), $this->evm);
    }

    /**
     * pre-persist test case
     */
    public function testCreateWithPrePersistListener()
    {
        $listener = new PrePersistListener($this->evm);

        $gatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Role';
        $entityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $entity = new $entityClass;
        $entity->setName('guest');
        $this->persister->setTableGateway($entityClass, $gatewayClass);

        $this->persister->create($entity);
        // Check the listener was invoked by calling Zend#create
        $this->assertTrue($listener->prePersistInvoked);
        // Check it was invoked BEFORE the entity was inserted in the database
        // and the id was assigned
        $this->assertTrue($listener->idWasNullAtPrePersistStage);
        // And be sure the id was assigned after the entity was inserted in the
        // database
        $this->assertNotNull($entity->getId());

        $this->evm->removeEventListener(LifecycleEvent::PRE_PERSIST, $listener);
    }

    /**
     * post-persist test case
     */
    public function testCreateWithPostPersistListener()
    {
        $listener = new PostPersistListener($this->evm);

        $gatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Role';
        $entityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $entity = new $entityClass;
        $entity->setName('guest');
        $this->persister->setTableGateway($entityClass, $gatewayClass);

        $this->persister->create($entity);
        // Check the listener was invoked by calling Zend#create
        $this->assertTrue($listener->postPersistInvoked);
        // Check it was invoked AFTER the entity was inserted in the database
        // and the id was assigned
        $this->assertTrue($listener->idWasAssignedAtPostPersistStage);

        $this->evm->removeEventListener(LifecycleEvent::POST_PERSIST, $listener);
    }

    /**
     * pre-update test cases
     */
    public function testUpdateWithPreUpdateListenerOnEntityThatIsNotManaged()
    {
        $listener = new PreUpdateListener($this->evm);

        $gatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Role';
        $entityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $entity = new $entityClass;
        $entity->setName('guest');
        $this->persister->setTableGateway($entityClass, $gatewayClass);

        try {
            // As the entity is not managed, Zend#update should throw an exception
            $this->persister->update($entity);
        } catch (\Serquant\Persistence\Exception\RuntimeException $e) {
            // Check the listener was not invoked
            $this->assertFalse($listener->preUpdateInvoked);
        }

        $this->evm->removeEventListener(LifecycleEvent::PRE_UPDATE, $listener);
    }

    public function testUpdateWithPreUpdateListenerOnEntityThatHasNotChanged()
    {
        $listener = new PreUpdateListener($this->evm);

        $gatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Role';
        $entityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $entity = new $entityClass;
        $entity->setId(1);
        $entity->setName('guest');
        $this->persister->setTableGateway($entityClass, $gatewayClass);

        // Put the entity in the IdentityMap
        $mapProp = new \ReflectionProperty('Serquant\Persistence\Zend', 'loadedMap');
        $mapProp->setAccessible(true);
        $map = $mapProp->getValue($this->persister);
        $map->put($entity, array('id' => 1));

        // As the entity has not changed, Zend#update should not notify the
        // preUpdate event
        $this->persister->update($entity);
        // Check the listener was not invoked
        $this->assertFalse($listener->preUpdateInvoked);

        $this->evm->removeEventListener(LifecycleEvent::PRE_UPDATE, $listener);
    }

    public function testUpdateWithPreUpdateListenerOnEntityThatHasChanged()
    {
        // Combine listener and gateway
        $listener = new CombinedUpdateListener();
        $this->evm->addEventListener(LifecycleEvent::PRE_UPDATE, $listener);

        $entityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $entity = new $entityClass;
        $entity->setId(1);
        $entity->setName('guest');
        $this->persister->setTableGateway($entityClass, $listener);

        // Put the entity in the IdentityMap
        $mapProp = new \ReflectionProperty('Serquant\Persistence\Zend', 'loadedMap');
        $mapProp->setAccessible(true);
        $map = $mapProp->getValue($this->persister);
        $map->put($entity, array('id' => 1));

        // Change the entity
        $entity->setName('admin');

        // As the entity has changed, Zend#update should notify the preUpdate event
        $this->persister->update($entity);
        // Check the listener was invoked
        $this->assertTrue($listener->preUpdateInvoked);
        // Check Table#update was invoked
        $this->assertTrue($listener->gatewayUpdateInvoked);
        // and check if preUpdate whas invoked before Table#update
        $this->assertTrue($listener->preUpdateInvokedBeforeGatewayUpdate);

        $this->evm->removeEventListener(LifecycleEvent::PRE_UPDATE, $listener);
    }

    /**
     * post-update test cases
     */
    public function testUpdateWithPostUpdateListener()
    {
        // Combine listener and gateway
        $listener = new CombinedUpdateListener();
        $this->evm->addEventListener(LifecycleEvent::POST_UPDATE, $listener);

        $entityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $entity = new $entityClass;
        $entity->setId(1);
        $entity->setName('guest');
        $this->persister->setTableGateway($entityClass, $listener);

        // Put the entity in the IdentityMap
        $mapProp = new \ReflectionProperty('Serquant\Persistence\Zend', 'loadedMap');
        $mapProp->setAccessible(true);
        $map = $mapProp->getValue($this->persister);
        $map->put($entity, array('id' => 1));

        // Change the entity
        $entity->setName('admin');

        // As the entity has changed, Zend#update should notify the postUpdate event
        $this->persister->update($entity);
        // Check the listener was invoked
        $this->assertTrue($listener->postUpdateInvoked);
        // Check Table#update was invoked
        $this->assertTrue($listener->gatewayUpdateInvoked);
        // and check if postUpdate whas invoked after Table#update
        $this->assertTrue($listener->postUpdateInvokedAfterGatewayUpdate);

        $this->evm->removeEventListener(LifecycleEvent::POST_UPDATE, $listener);
    }

    public function testUpdateReturningBadCountWithPostUpdateListener()
    {
        $listener = new PostUpdateListener($this->evm);

        // Mock the gateway to return a bad count (0) on update
        $gatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Role';
        $gateway = $this->getMock($gatewayClass, array('update'));
        $gateway->expects($this->any())
                ->method('update')
                ->will($this->returnValue(0));

        $entityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $entity = new $entityClass;
        $entity->setId(1);
        $entity->setName('guest');

        $this->persister->setTableGateway($entityClass, $gateway);

        // Put the entity in the IdentityMap
        $mapProp = new \ReflectionProperty('Serquant\Persistence\Zend', 'loadedMap');
        $mapProp->setAccessible(true);
        $map = $mapProp->getValue($this->persister);
        $map->put($entity, array('id' => 1));

        // Change the entity
        $entity->setName('admin');

        try {
            // As Table#update will return 0, an exception will be thrown
            $this->persister->update($entity);
        } catch (\Serquant\Persistence\Exception\NoResultException $e) {
            // Check the listener was invoked nevertheless
            $this->assertTrue($listener->postUpdateInvoked);
        }

        $this->evm->removeEventListener(LifecycleEvent::POST_UPDATE, $listener);
    }

    /**
     * pre-remove test cases
     */
    public function testDeleteWithPreRemoveListenerOnEntityThatIsNotManaged()
    {
        $listener = new PreRemoveListener($this->evm);

        $gatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Role';
        $entityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $entity = new $entityClass;
        $entity->setName('guest');
        $this->persister->setTableGateway($entityClass, $gatewayClass);

        try {
            // As the entity is not managed, Zend#delete should throw an exception
            $this->persister->delete($entity);
        } catch (\Serquant\Persistence\Exception\RuntimeException $e) {
            // Check the listener was not invoked
            $this->assertFalse($listener->preRemoveInvoked);
        }

        $this->evm->removeEventListener(LifecycleEvent::PRE_REMOVE, $listener);
    }

    public function testDeleteWithPreRemoveListener()
    {
        // Combine listener and gateway
        $listener = new CombinedDeleteListener();
        $this->evm->addEventListener(LifecycleEvent::PRE_REMOVE, $listener);

        $entityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $entity = new $entityClass;
        $entity->setId(1);
        $entity->setName('guest');
        $this->persister->setTableGateway($entityClass, $listener);

        // Put the entity in the IdentityMap
        $mapProp = new \ReflectionProperty('Serquant\Persistence\Zend', 'loadedMap');
        $mapProp->setAccessible(true);
        $map = $mapProp->getValue($this->persister);
        $map->put($entity, array('id' => 1));

        $this->persister->delete($entity);
        // Check the listener was invoked
        $this->assertTrue($listener->preRemoveInvoked);
        // Check Table#delete was invoked
        $this->assertTrue($listener->gatewayDeleteInvoked);
        // and check if preRemove whas invoked before Table#delete
        $this->assertTrue($listener->preRemoveInvokedBeforeGatewayDelete);

        $this->evm->removeEventListener(LifecycleEvent::PRE_REMOVE, $listener);
    }

    /**
     * post-remove test cases
     */
    public function testDeleteWithPostRemoveListener()
    {
        // Combine listener and gateway
        $listener = new CombinedDeleteListener();
        $this->evm->addEventListener(LifecycleEvent::POST_REMOVE, $listener);

        $entityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $entity = new $entityClass;
        $entity->setId(1);
        $entity->setName('guest');
        $this->persister->setTableGateway($entityClass, $listener);

        // Put the entity in the IdentityMap
        $mapProp = new \ReflectionProperty('Serquant\Persistence\Zend', 'loadedMap');
        $mapProp->setAccessible(true);
        $map = $mapProp->getValue($this->persister);
        $map->put($entity, array('id' => 1));

        $this->persister->delete($entity);
        // Check the listener was invoked
        $this->assertTrue($listener->postRemoveInvoked);
        // Check Table#delete was invoked
        $this->assertTrue($listener->gatewayDeleteInvoked);
        // and check if postRemove whas invoked after Table#update
        $this->assertTrue($listener->postRemoveInvokedAfterGatewayDelete);

        $this->evm->removeEventListener(LifecycleEvent::POST_REMOVE, $listener);
    }

    public function testDeleteReturningBadCountWithPostRemoveListener()
    {
        $listener = new PostRemoveListener($this->evm);

        // Mock the gateway to return a bad count (0) on delete
        $gatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Role';
        $gateway = $this->getMock($gatewayClass, array('delete'));
        $gateway->expects($this->any())
                ->method('delete')
                ->will($this->returnValue(0));

        $entityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $entity = new $entityClass;
        $entity->setId(1);
        $entity->setName('guest');

        $this->persister->setTableGateway($entityClass, $gateway);

        // Put the entity in the IdentityMap
        $mapProp = new \ReflectionProperty('Serquant\Persistence\Zend', 'loadedMap');
        $mapProp->setAccessible(true);
        $map = $mapProp->getValue($this->persister);
        $map->put($entity, array('id' => 1));

        try {
            // As Table#delete will return 0, an exception will be thrown
            $this->persister->delete($entity);
        } catch (\Serquant\Persistence\Exception\NoResultException $e) {
            // Check the listener was invoked nevertheless
            $this->assertTrue($listener->postRemoveInvoked);
        }

        $this->evm->removeEventListener(LifecycleEvent::POST_REMOVE, $listener);
    }
}

class PrePersistListener
{
    public $prePersistInvoked = false;

    public $idWasNullAtPrePersistStage = false;

    public function __construct(EventManager $evm)
    {
        $evm->addEventListener(LifecycleEvent::PRE_PERSIST, $this);
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof \Serquant\Resource\Persistence\Zend\Role) {
            $this->prePersistInvoked = true;
            $id = $entity->getId();
            if ($id === null) {
                $this->idWasNullAtPrePersistStage = true;
            }
        }
    }
}

class PostPersistListener
{
    public $postPersistInvoked = false;

    public $idWasAssignedAtPostPersistStage = false;

    public function __construct(EventManager $evm)
    {
        $evm->addEventListener(LifecycleEvent::POST_PERSIST, $this);
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof \Serquant\Resource\Persistence\Zend\Role) {
            $this->postPersistInvoked = true;
            $id = $entity->getId();
            if ($id) {
                $this->idWasAssignedAtPostPersistStage = true;
            }
        }
    }
}

class PreUpdateListener
{
    public $preUpdateInvoked = false;

    public function __construct(EventManager $evm)
    {
        $evm->addEventListener(LifecycleEvent::PRE_UPDATE, $this);
    }

    public function preUpdate(PreUpdateLifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof \Serquant\Resource\Persistence\Zend\Role) {
            $this->preUpdateInvoked = true;
        }
    }
}

class PostUpdateListener
{
    public $postUpdateInvoked = false;

    public function __construct(EventManager $evm)
    {
        $evm->addEventListener(LifecycleEvent::POST_UPDATE, $this);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof \Serquant\Resource\Persistence\Zend\Role) {
            $this->postUpdateInvoked = true;
        }
    }
}

class CombinedUpdateListener
    extends \Serquant\Resource\Persistence\Zend\Db\Table\Role
{
    public $gatewayUpdateInvoked = false;

    public $preUpdateInvoked = false;

    public $preUpdateInvokedBeforeGatewayUpdate = false;

    public $postUpdateInvoked = false;

    public $postUpdateInvokedAfterGatewayUpdate = false;

    public function update(array $data, $id)
    {
        $this->gatewayUpdateInvoked = true;
        return 1;
    }

    public function preUpdate(PreUpdateLifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof \Serquant\Resource\Persistence\Zend\Role) {
            $this->preUpdateInvoked = true;
            if ($this->gatewayUpdateInvoked === false) {
                $this->preUpdateInvokedBeforeGatewayUpdate = true;
            }
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof \Serquant\Resource\Persistence\Zend\Role) {
            $this->postUpdateInvoked = true;
            if ($this->gatewayUpdateInvoked === true) {
                $this->postUpdateInvokedAfterGatewayUpdate = true;
            }
        }
    }
}

class PreRemoveListener
{
    public $preRemoveInvoked = false;

    public function __construct(EventManager $evm)
    {
        $evm->addEventListener(LifecycleEvent::PRE_REMOVE, $this);
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof \Serquant\Resource\Persistence\Zend\Role) {
            $this->preRemoveInvoked = true;
        }
    }
}

class PostRemoveListener
{
    public $postRemoveInvoked = false;

    public function __construct(EventManager $evm)
    {
        $evm->addEventListener(LifecycleEvent::POST_REMOVE, $this);
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof \Serquant\Resource\Persistence\Zend\Role) {
            $this->postRemoveInvoked = true;
        }
    }
}

class CombinedDeleteListener
    extends \Serquant\Resource\Persistence\Zend\Db\Table\Role
{
    public $gatewayDeleteInvoked = false;

    public $preRemoveInvoked = false;

    public $preRemoveInvokedBeforeGatewayDelete = false;

    public $postRemoveInvoked = false;

    public $postRemoveInvokedAfterGatewayDelete = false;

    public function delete($id)
    {
        $this->gatewayDeleteInvoked = true;
        return 1;
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof \Serquant\Resource\Persistence\Zend\Role) {
            $this->preRemoveInvoked = true;
            if ($this->gatewayDeleteInvoked === false) {
                $this->preRemoveInvokedBeforeGatewayDelete = true;
            }
        }
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof \Serquant\Resource\Persistence\Zend\Role) {
            $this->postRemoveInvoked = true;
            if ($this->gatewayDeleteInvoked === true) {
                $this->postRemoveInvokedAfterGatewayDelete = true;
            }
        }
    }
}
