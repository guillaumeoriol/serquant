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

use Serquant\Event\LifecycleEventArgs;
use Serquant\Persistence\Zend\Configuration;

/**
 * Test class on lifecycle events in Zend persister.
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class PersisterLifecycleEventsTest
    extends \Serquant\Resource\Persistence\ZendTestCase
{
    protected $evm;
    protected $persister;

    protected function setUp()
    {
        $this->evm = new \Doctrine\Common\EventManager();
        $config = new Configuration();
        $config->setEventManager($this->evm);
        $this->persister = new \Serquant\Persistence\Zend\Persister($config);
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