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

use Serquant\Event\LifecycleEvent;

/**
 * Test class for LifecycleEvent
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class LifecycleEventTest extends \PHPUnit_Framework_TestCase
{
    public function testNoDuplicatesInConstants()
    {
        $constants = array(
            LifecycleEvent::PRE_PERSIST,
            LifecycleEvent::POST_PERSIST,
            LifecycleEvent::PRE_UPDATE,
            LifecycleEvent::POST_UPDATE,
            LifecycleEvent::PRE_REMOVE,
            LifecycleEvent::POST_REMOVE
        );
        $withoutDuplicates = array_unique($constants);
        $this->assertEquals($constants, $withoutDuplicates);
    }
}