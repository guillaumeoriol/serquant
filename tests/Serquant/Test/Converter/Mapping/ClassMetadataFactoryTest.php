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

use Doctrine\Common\Annotations\AnnotationRegistry;
use Serquant\Converter\Mapping\ClassMetadataFactory;
use Serquant\Converter\Mapping\Loader\AnnotationLoader;
use Serquant\DependencyInjection\Factory\AnnotationReaderFactory;

/**
 * Test class for the ClassMetadataFactory.
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ClassMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $reader;

    private $loader;

    protected function setUp()
    {
        AnnotationRegistry::reset();
        $autoloadNamespaces = array(
    		'Serquant\Converter\Mapping' => APPLICATION_ROOT . '/library'
		);
        $config = array(
        	'annotationAutoloadNamespaces' => $autoloadNamespaces,
    		'ignoreNotImportedAnnotations' => true
        );

        $this->reader = AnnotationReaderFactory::get($config);
        $this->loader = new AnnotationLoader($this->reader);
    }

    public function testGetClassMetadataWithoutCache()
    {
        $class = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $factory = new ClassMetadataFactory($this->loader);
        $metadata = $factory->getClassMetadata($class);
        $this->assertInstanceOf('Serquant\Converter\Mapping\ClassMetadataInterface', $metadata);

        $property = new \ReflectionProperty($factory, 'loadedClasses');
        $property->setAccessible(true);
        $loadedClasses = $property->getValue($factory);
        $this->assertTrue(isset($loadedClasses[$class]));

        // Do it a second time to retrieve the metadata from the loadedClasses
        $metadata = $factory->getClassMetadata($class);
        $this->assertInstanceOf('Serquant\Converter\Mapping\ClassMetadataInterface', $metadata);
    }

    public function testGetClassMetadataWithInheritance()
    {
        $parentClass = 'Serquant\Resource\Converter\Informer';
        $class = 'Serquant\Resource\Converter\SubclassA';
        $factory = new ClassMetadataFactory($this->loader);
        $metadata = $factory->getClassMetadata($class);
        $this->assertInstanceOf('Serquant\Converter\Mapping\ClassMetadataInterface', $metadata);

        // Check if both classes have been loaded
        $property = new \ReflectionProperty($factory, 'loadedClasses');
        $property->setAccessible(true);
        $loadedClasses = $property->getValue($factory);
        $this->assertTrue(isset($loadedClasses[$class]));
        $this->assertTrue(isset($loadedClasses[$parentClass]));

        // Check if properties of both classes have been merged
        $this->assertEquals(0, count(array_diff(
            array('id', 'name', 'specificToA', 'savedAt', 'savedBy'),
            array_keys($metadata->getProperties())
        )));
    }

    public function testGetClassMetadataWithArrayCache()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $class = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $factory = new ClassMetadataFactory($this->loader, $cache);
        $metadata = $factory->getClassMetadata($class);
        $this->assertInstanceOf('Serquant\Converter\Mapping\ClassMetadataInterface', $metadata);

        // Remove metadata from loadedClass to retrieve it from cache
        $property = new \ReflectionProperty($factory, 'loadedClasses');
        $property->setAccessible(true);
        $loadedClasses = $property->setValue($factory, array());
        $metadata = $factory->getClassMetadata($class);
        $this->assertInstanceOf('Serquant\Converter\Mapping\ClassMetadataInterface', $metadata);
    }
}