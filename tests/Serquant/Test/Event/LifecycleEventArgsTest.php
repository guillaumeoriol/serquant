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
namespace Serquant\Test\Event;

use Serquant\Event\LifecycleEventArgs;
use Serquant\Persistence\Zend\Configuration;

/**
 * Test class for LifecycleEventArgs
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class LifecycleEventArgsTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    protected $persister;

    protected function setUp()
    {
        $evm = new \Doctrine\Common\EventManager();
        $config = new Configuration();
        $config->setEventManager($evm);
        $this->persister = new \Serquant\Persistence\Zend\Persister($config);
    }

    /**
     * @covers Serquant\Event\LifecycleEventArgs::__construct
     * @covers Serquant\Event\LifecycleEventArgs::getEntity
     */
    public function testGetEntity()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $args = new LifecycleEventArgs($entity, $this->persister);
        $actual = $args->getEntity();
        $this->assertTrue(is_object($actual));
        $this->assertSame($entity, $actual);
    }

    /**
     * @covers Serquant\Event\LifecycleEventArgs::getPersister
     */
    public function testGetPersister()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $args = new LifecycleEventArgs($entity, $this->persister);
        $actual = $args->getPersister();
        $this->assertInstanceOf('Serquant\Persistence\Zend\Persister', $actual);
        $this->assertSame($this->persister, $actual);
    }
}