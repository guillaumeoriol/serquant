<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Factory
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Test\DependencyInjection\Factory;

use Serquant\DependencyInjection\Factory\AnnotationReaderFactory;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Short description.
 *
 * Long description.
 *
 * @category Serquant
 * @package  Factory
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class AnnotationReaderFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetWithEmptyConfig()
    {
        $config = array();

        $reader = AnnotationReaderFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\Annotations\AnnotationReader', $reader);
    }

    public function testGetWithWrongAutoloadNamespaces()
    {
        $config = array('annotationAutoloadNamespaces' => false);

        $this->setExpectedException('Serquant\DependencyInjection\Exception\InvalidArgumentException');
        $reader = AnnotationReaderFactory::get($config);
    }

    public function testGetWithAutoloadNamespaces()
    {
        $autoloadNamespaces = array(
    		'Symfony\Component\Validator\Constraints' => TEST_PATH . '/library'
		);
        $config = array('annotationAutoloadNamespaces' => $autoloadNamespaces);

        $reader = AnnotationReaderFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\Annotations\AnnotationReader', $reader);

        $property = new \ReflectionProperty('Doctrine\Common\Annotations\AnnotationRegistry', 'autoloadNamespaces');
        $property->setAccessible(true);
        $value = $property->getValue();
        $this->assertEquals($autoloadNamespaces, $value);
    }

    // @todo Write a public function testGetWithMissingAnnotationFile()
    // for which PHP error would be catched and converted to a
    // PHPUnit_Framework_Error exception.

    public function testGetWithSingleAnnotationFile()
    {
        $config = array(
        	'annotationFile' => TEST_PATH . '/library/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
        );

        $reader = AnnotationReaderFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\Annotations\AnnotationReader', $reader);
        $this->assertTrue(class_exists('Doctrine\ORM\Mapping\Entity', false));
    }

    public function testGetWithMultipleAnnotationFiles()
    {
        $config = array(
        	'annotationFile' => array(
                TEST_PATH . '/library/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php',
                TEST_PATH . '/library/Doctrine/Common/Annotations/Annotation/IgnoreAnnotation.php'
            )
        );

        $reader = AnnotationReaderFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\Annotations\AnnotationReader', $reader);
        $this->assertTrue(class_exists('Doctrine\ORM\Mapping\Entity', false));
        $this->assertTrue(class_exists('Doctrine\Common\Annotations\Annotation\IgnoreAnnotation', false));
    }

    public function testGetWithSingleAnnotationLoader()
    {
        $loader = function ($class) { return false; };
        $config = array('annotationLoaders' => $loader);

        $reader = AnnotationReaderFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\Annotations\AnnotationReader', $reader);

        $property = new \ReflectionProperty('Doctrine\Common\Annotations\AnnotationRegistry', 'loaders');
        $property->setAccessible(true);
        $value = $property->getValue();
        $this->assertEquals($loader, current($value));
    }

    public function testGetWithMultipleAnnotationLoaders()
    {
        $loader0 = function ($class) { return false; };
        $loader1 = function ($class) { return false; };
        $config = array('annotationLoaders' => array($loader0, $loader1));

        $reader = AnnotationReaderFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\Annotations\AnnotationReader', $reader);

        $property = new \ReflectionProperty('Doctrine\Common\Annotations\AnnotationRegistry', 'loaders');
        $property->setAccessible(true);
        $value = $property->getValue();
        $this->assertEquals($loader0, $value[0]);
        $this->assertEquals($loader1, $value[1]);
    }

    public function testGetWithIgnoreNotImportedAnnotations()
    {
        $config = array('ignoreNotImportedAnnotations' => true);

        $reader = AnnotationReaderFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\Annotations\AnnotationReader', $reader);

        $property = new \ReflectionProperty($reader, 'parser');
        $property->setAccessible(true);
        $parser = $property->getValue($reader);
        $this->assertAttributeEquals(true, 'ignoreNotImportedAnnotations', $parser);

        $config = array('ignoreNotImportedAnnotations' => false);

        $reader = AnnotationReaderFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\Annotations\AnnotationReader', $reader);

        $property = new \ReflectionProperty($reader, 'parser');
        $property->setAccessible(true);
        $parser = $property->getValue($reader);
        $this->assertAttributeEquals(false, 'ignoreNotImportedAnnotations', $parser);
    }

    public function testGetWithWrongNamespaceAlias()
    {
        $config = array('namespaceAliases' => false);

        $this->setExpectedException('Serquant\DependencyInjection\Exception\InvalidArgumentException');
        $reader = AnnotationReaderFactory::get($config);
    }

    public function testGetWithNamespaceAlias()
    {
        $aliases = array('Doctrine\ORM\Mapping\\' => 'orm');
        $config = array('namespaceAliases' => $aliases);

        $reader = AnnotationReaderFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\Annotations\AnnotationReader', $reader);

        $property = new \ReflectionProperty($reader, 'parser');
        $property->setAccessible(true);
        $parser = $property->getValue($reader);
        $this->assertAttributeEquals(array_flip($aliases), 'namespaceAliases', $parser);
    }

    public function testGetWithWrongCacheDriver()
    {
        $config = array('cache' => 'wrong');

        $this->setExpectedException('Serquant\DependencyInjection\Exception\InvalidArgumentException');
        $reader = AnnotationReaderFactory::get($config);
    }

    public function testGetWithArrayCache()
    {
        $config = array('cache' => 'array');

        $reader = AnnotationReaderFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\Annotations\CachedReader', $reader);
    }

    public function testGetWithArrayCacheDebug()
    {
        $config = array(
        	'cache' => 'array',
        	'cacheDebug' => true
        );

        $reader = AnnotationReaderFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\Annotations\CachedReader', $reader);
        $this->assertAttributeEquals(true, 'debug', $reader);
    }
}