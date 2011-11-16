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

use Serquant\Converter\Mapping\ClassMetadata;
use Serquant\Converter\Mapping\Property;

/**
 * Test class for the ClassMetadata.
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ClassMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testGetReflectionClass()
    {
        $class = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $metadata = new ClassMetadata($class);
        $refl = $metadata->getReflectionClass();
        $this->assertInstanceOf('ReflectionClass', $refl);
        $this->assertEquals($class, $refl->getName());
    }

    public function testAddProperty()
    {
        $property = new Property(array('type' => 'integer'));

        $metadata = new ClassMetadata('MyClass');
        $metadata->addProperty('id', $property);

        $reflProp = new \ReflectionProperty($metadata, 'conversionProperties');
        $reflProp->setAccessible(true);
        $conversionProperties = $reflProp->getValue($metadata);
        $this->assertArrayHasKey('id', $conversionProperties);
        $this->assertEquals($property, $conversionProperties['id']);
    }

    public function testMergeProperties()
    {
        $property1 = new Property(array('type' => 'integer'));
        $property2 = new Property(array('type' => 'boolean'));
        $property3 = new Property(array('type' => 'string'));

        $metadata1 = new ClassMetadata('ClassA');
        $metadata1->addProperty('id', $property1);
        $metadata1->addProperty('isDummy', $property2);

        $metadata2 = new ClassMetadata('ClassB');
        $metadata2->addProperty('name', $property3);

        $metadata1->mergeProperties($metadata2);
        $properties = $metadata1->getProperties();
        $this->assertEquals(3, count($properties));
        $this->assertEquals($property3, $properties['name']);
    }
}
