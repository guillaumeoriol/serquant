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

use Serquant\Converter\Mapping\Property;

/**
 * Test class of the Property.
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class PropertyTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultValues()
    {
        $property = new Property(array());
        $this->assertEquals('string', $property->getType());
        $this->assertEquals('1', $property->getMultiplicity());
    }

    public function testIsConvertible()
    {
        $property = new Property(array('type' => 'string'));
        $this->assertTrue($property->isConvertible());

        $property = new Property(array('type' => 'int'));
        $this->assertTrue($property->isConvertible());

        $property = new Property(array('type' => 'MyClass'));
        $this->assertFalse($property->isConvertible());
    }

    public function testGetCanonicalType()
    {
        $property = new Property(array('type' => 'int'));
        $this->assertEquals('integer', $property->getType());

        $property = new Property(array('type' => 'integer'));
        $this->assertEquals('integer', $property->getType());

        $property = new Property(array('type' => 'bool'));
        $this->assertEquals('boolean', $property->getType());

        $property = new Property(array('type' => 'double'));
        $this->assertEquals('float', $property->getType());

        $property = new Property(array('type' => 'Name\Spaced\Classname'));
        $this->assertEquals('Name\Spaced\Classname', $property->getType());
    }

    public function testSetInvalidMultiplicity()
    {
        $this->setExpectedException('Serquant\Converter\Exception\DomainException');
        $property = new Property(array('multiplicity' => '?'));
    }

    public function testSetMultiplicity()
    {
        $property = new Property(array('multiplicity' => '1'));
        $this->assertEquals('1', $property->getMultiplicity());
        $this->assertFalse($property->isMultivalued());

        $property = new Property(array('multiplicity' => '*'));
        $this->assertEquals('*', $property->getMultiplicity());
        $this->assertTrue($property->isMultivalued());
    }
}