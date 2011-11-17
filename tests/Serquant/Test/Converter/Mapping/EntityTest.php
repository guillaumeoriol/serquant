<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Test\Converter\Mapping;

use Serquant\Converter\Mapping\Entity;

/**
 * Test class of the Entity.
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class EntityTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultValues()
    {
        $entity = new Entity(array());
        $this->assertNull($entity->getPrefix());
    }

    public function testConstruct()
    {
        $prefix = '/whatever/';

        $entity = new Entity(array('prefix' => $prefix));
        $this->assertEquals($prefix, $entity->getPrefix());
    }

    public function testSetPrefix()
    {
        $prefix = '/whatever/';

        $entity = new Entity(array());
        $this->assertNull($entity->getPrefix());

        $entity->setPrefix($prefix);
        $this->assertEquals($prefix, $entity->getPrefix());
    }
}