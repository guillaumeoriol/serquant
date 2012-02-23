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

use Serquant\Event\PreUpdateLifecycleEventArgs;

/**
 * Test class for PreUpdateLifecycleEventArgs
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class PreUpdateLifecycleEventArgsTest
    extends \Serquant\Resource\Persistence\ZendTestCase
{
    protected $persister;

    protected function setUp()
    {
        $evm = new \Doctrine\Common\EventManager();
        $this->persister = new \Serquant\Persistence\Zend\Persister(array(), $evm);
    }

    /**
     * @covers Serquant\Event\PreUpdateLifecycleEventArgs::__construct
     * @covers Serquant\Event\PreUpdateLifecycleEventArgs::getOriginalState
     */
    public function testGetOriginalState()
    {
        $old = new \Serquant\Resource\Persistence\Zend\User();
        $new = new \Serquant\Resource\Persistence\Zend\User();
        $args = new PreUpdateLifecycleEventArgs($new, $this->persister, $old);
        $actual = $args->getOriginalState();
        $this->assertTrue(is_object($actual));
        $this->assertSame($old, $actual);
        $this->assertNotSame($new, $actual);
    }
}