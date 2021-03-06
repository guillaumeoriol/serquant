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
namespace Serquant\Test\DependencyInjection;

use Serquant\DependencyInjection\ContainerBuilder;

class ContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testSet()
    {
        $resource = new \stdClass();
        $id = \spl_object_hash($resource);

        $dic = new ContainerBuilder();
        $dic->__set('dummy', $resource);
        $this->assertEquals($id, \spl_object_hash($dic->dummy));
    }

    public function testIsset()
    {
        $resource = new \stdClass();

        $dic = new ContainerBuilder();
        $dic->dummy = $resource;
        $this->assertTrue($dic->__isset('dummy'));
        $this->assertFalse($dic->__isset('foo'));
    }

    public function testGet()
    {
        $resource = new \stdClass();
        $id = \spl_object_hash($resource);

        $dic = new ContainerBuilder();
        $dic->dummy = $resource;
        $this->assertEquals($id, \spl_object_hash($dic->__get('dummy')));
    }
}