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
namespace Serquant\Test\DependencyInjection\Factory;

use Serquant\DependencyInjection\Factory\ValidatorFactory;
use Symfony\Component\Validator\Validator;

/**
 * Test class for the ValidatorFactory.
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ValidatorFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetWithEmptyMappingFiles()
    {
        $config = array('mappingFiles' => array());

        $this->setExpectedException('Symfony\Component\Validator\Exception\MappingException');
        $validator = ValidatorFactory::get($config);
    }

    public function testGetWithInvalidMappingFiles()
    {
        $config = array('mappingFiles' => array('bad.extension'));
        $this->setExpectedException('Symfony\Component\Validator\Exception\MappingException');
        $validator = ValidatorFactory::get($config);
    }

    public function testGetWithMissingMappingFiles()
    {
        $config = array('mappingFiles' => array('dummy.xml'));
        $this->setExpectedException('Symfony\Component\Validator\Exception\MappingException');
        $validator = ValidatorFactory::get($config);
    }

    public function testGetWithSingleExistingMappingFile()
    {
        $config = array('mappingFiles' => TEST_PATH . '/Serquant/Resource/config/validation.yml');
        $validator = ValidatorFactory::get($config);
        $this->assertInstanceOf('Symfony\Component\Validator\ValidatorInterface', $validator);
    }

    public function testGetWithMultipleExistingMappingFiles()
    {
        $config = array('mappingFiles' => array(
            TEST_PATH . '/Serquant/Resource/config/validation.yml',
            TEST_PATH . '/Serquant/Resource/config/validation.xml'
        ));
        $validator = ValidatorFactory::get($config);
        $this->assertInstanceOf('Symfony\Component\Validator\ValidatorInterface', $validator);
    }

    public function testGetWithEmptyAnnotations()
    {
        $config = array('annotations' => array());
        $validator = ValidatorFactory::get($config);
        $this->assertInstanceOf('Symfony\Component\Validator\ValidatorInterface', $validator);
    }

    public function testGetWithAnnotationsWithAnnotationReader()
    {
        $reader = \Serquant\DependencyInjection\Factory\AnnotationReaderFactory::get(array());
        $config = array('annotations' => array(
        	'annotationReader' => $reader
        ));
        $validator = ValidatorFactory::get($config);
        $this->assertInstanceOf('Symfony\Component\Validator\ValidatorInterface', $validator);
    }

    public function testGetWithAnnotationsWithoutAnnotationReader()
    {
        $config = array(
            'annotations' => array(
                'annotationFile' => TEST_PATH . '/library/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php',
            	'annotationAutoloadNamespaces' => array( // Leading backslash forbidden
            		'Symfony\Component\Validator\Constraints' => TEST_PATH . '/library',
            		'Domain\Entity' => APPLICATION_ROOT . '/application'
            	),
                'ignoreNotImportedAnnotations' => true,
                'cache' => 'apc',
                'cacheDebug' => true
            )
        );
        $validator = ValidatorFactory::get($config);
        $this->assertInstanceOf('Symfony\Component\Validator\ValidatorInterface', $validator);
    }

    public function testGetWithStaticMethodLoader()
    {
        $config = array('staticMethod' => 'loadValidatorMetadata');
        $validator = ValidatorFactory::get($config);
        $this->assertInstanceOf('Symfony\Component\Validator\ValidatorInterface', $validator);
    }
/*
    public function testSetClassMetadataFactory()
    {
        $config = array('mappingFiles' => array(TEST_PATH . '/Serquant/Resource/config/validation.yml'));
        $validator = ValidatorFactory::get($config);

        $property = new \ReflectionProperty('Serquant\DependencyInjection\Factory\ValidatorFactory', 'defaultContext');
        $property->setAccessible(true);
        $originalContext = $property->getValue($validator);

        $reader = \Serquant\DependencyInjection\Factory\AnnotationReaderFactory::get(array());
        $loader = new \Symfony\Component\Validator\Mapping\Loader\AnnotationLoader($reader);
        $factory = new \Symfony\Component\Validator\Mapping\ClassMetadataFactory($loader);
        $validator->setClassMetadataFactory($factory);

        $this->assertSame($originalContext, $property->getValue($validator));
    }
*/
}