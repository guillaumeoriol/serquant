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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Serquant\Converter\Mapping\ClassMetadata;
use Serquant\Converter\Mapping\Loader\AnnotationLoader;
use Serquant\Converter\Mapping\Property;
use Serquant\DependencyInjection\Factory\AnnotationReaderFactory;

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
        $class = 'Serquant\Resource\Converter\UserWithPublicProperties';
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

    public function testSetIdentifier()
    {
        $metadata = new ClassMetadata('MyClass');
        $metadata->setIdentifier('a');

        $this->assertTrue($metadata->isIdentifier('a'));
        $this->assertFalse($metadata->isIdentifier('b'));
    }

    public function testSetIdentifierWithDuplicate()
    {
        $metadata = new ClassMetadata('MyClass');
        $metadata->setIdentifier('a');

        $this->setExpectedException('Serquant\Converter\Exception\RuntimeException');
        $metadata->setIdentifier('a');
    }

    public function testMergeIdentifier()
    {
        $metadata1 = new ClassMetadata('ClassA');
        $metadata2 = new ClassMetadata('ClassB');
        $metadata2->setIdentifier('B');
        $metadata1->mergeIdentifierPrefix($metadata2);
        $this->assertTrue($metadata1->isIdentifier('B'));

        $metadata1 = new ClassMetadata('ClassA');
        $metadata1->setIdentifier('A');
        $metadata2 = new ClassMetadata('ClassB');
        $metadata2->setIdentifier('B');
        $metadata1->mergeIdentifierPrefix($metadata2);
        $this->assertFalse($metadata1->isIdentifier('A'));
        $this->assertTrue($metadata1->isIdentifier('B'));

        $metadata1 = new ClassMetadata('ClassA');
        $metadata1->setIdentifier('A');
        $metadata2 = new ClassMetadata('ClassB');
        $metadata1->mergeIdentifierPrefix($metadata2);
        $this->assertTrue($metadata1->isIdentifier('A'));
        $this->assertFalse($metadata1->isIdentifier('B'));
    }

    public function testMergeIdentifierPrefix()
    {
        $metadata1 = new ClassMetadata('ClassA');
        $metadata2 = new ClassMetadata('ClassB');
        $metadata2->setIdentifierPrefix('B');
        $metadata1->mergeIdentifierPrefix($metadata2);
        $this->assertEquals('B', $metadata1->getIdentifierPrefix());

        $metadata1 = new ClassMetadata('ClassA');
        $metadata1->setIdentifierPrefix('A');
        $metadata2 = new ClassMetadata('ClassB');
        $metadata2->setIdentifierPrefix('B');
        $metadata1->mergeIdentifierPrefix($metadata2);
        $this->assertEquals('B', $metadata1->getIdentifierPrefix());

        $metadata1 = new ClassMetadata('ClassA');
        $metadata1->setIdentifierPrefix('A');
        $metadata2 = new ClassMetadata('ClassB');
        $metadata1->mergeIdentifierPrefix($metadata2);
        $this->assertEquals('A', $metadata1->getIdentifierPrefix());
    }

    public function testGetMissingProperty()
    {
        $property = new Property(array('type' => 'integer'));

        $metadata = new ClassMetadata('MyClass');
        $metadata->addProperty('id', $property);

        $this->setExpectedException('Serquant\Converter\Exception\OutOfBoundsException');
        $metadata->getProperty('missing');
    }

    public function testSleepAndWakeup()
    {
        AnnotationRegistry::reset();
        $autoloadNamespaces = array(
    		'Serquant\Converter\Mapping' => APPLICATION_ROOT . '/library'
		);
        $config = array(
        	'annotationAutoloadNamespaces' => $autoloadNamespaces,
    		'ignoreNotImportedAnnotations' => true
        );

        $reader = AnnotationReaderFactory::get($config);
        $loader = new AnnotationLoader($reader);

        $class = 'Serquant\Resource\Converter\UserWithPublicProperties';
        $metadata = new ClassMetadata($class);
        $loader->loadClassMetadata($metadata);

        $expected = 'O:40:"Serquant\Converter\Mapping\ClassMetadata":4:{'
                  .   's:46:" Serquant\Converter\Mapping\ClassMetadata name";'
                  .     's:52:"Serquant\Resource\Converter\UserWithPublicProperties";'
                  .   's:52:" Serquant\Converter\Mapping\ClassMetadata identifier";'
                  .     'a:1:{i:0;s:2:"id";}'
                  .   's:58:" Serquant\Converter\Mapping\ClassMetadata identifierPrefix";'
                  .     's:11:"/rest/user/";'
                  .   's:62:" Serquant\Converter\Mapping\ClassMetadata conversionProperties";'
                  .     'a:3:{'
                  .       's:2:"id";'
                  .         'O:35:"Serquant\Converter\Mapping\Property":2:{'
                  .           's:41:" Serquant\Converter\Mapping\Property type";s:7:"integer";'
                  .           's:49:" Serquant\Converter\Mapping\Property multiplicity";s:1:"1";'
                  .         '}'
                  .       's:6:"status";'
                  .         'O:35:"Serquant\Converter\Mapping\Property":2:{'
                  .           's:41:" Serquant\Converter\Mapping\Property type";s:7:"boolean";'
                  .           's:49:" Serquant\Converter\Mapping\Property multiplicity";s:1:"1";'
                  .         '}'
                  .       's:8:"username";'
                  .         'O:35:"Serquant\Converter\Mapping\Property":2:{'
                  .           's:41:" Serquant\Converter\Mapping\Property type";s:6:"string";'
                  .           's:49:" Serquant\Converter\Mapping\Property multiplicity";s:1:"1";'
                  .         '}'
                  .     '}'
                  . '}';

        $serialized = serialize($metadata);
        $this->assertEquals($expected, $serialized);

        $deserialized = unserialize($serialized);
        // serialized
        $this->assertEquals($class, $deserialized->getClassName());
        // NOT serialized
        // Don't use the accessor as it implements lazy loading
        $reflProp = new \ReflectionProperty($metadata, 'reflClass');
        $reflProp->setAccessible(true);
        $this->assertInstanceOf('ReflectionClass', $reflProp->getValue($metadata));
        // serialized
        $this->assertTrue($deserialized->isIdentifier('id'));
        // serialized
        $this->assertEquals('/rest/user/', $deserialized->getIdentifierPrefix());
        // serialized
        $conversionProperties = $deserialized->getProperties();
        $this->assertEmpty(array_diff(
            array('id', 'status', 'username'),
            array_keys($conversionProperties)
        ));
        // NOT serialized
        foreach ($conversionProperties as $name => $value) {
            $this->assertInstanceOf('ReflectionProperty', $deserialized->getReflectionProperty($name));
        }
    }
}
