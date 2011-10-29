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
namespace Serquant\Test\Validator\DependencyInjection;

use Serquant\Validator\DependencyInjection\ValidatorFactory;
use Symfony\Component\Validator\Validator;

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

    public function testGetWithAnnotationsWithoutAutoloadNamespaces()
    {
        $config = array('annotations' => array());
        $this->setExpectedException('Serquant\Validator\Exception\InvalidArgumentException');
        $validator = ValidatorFactory::get($config);
    }

    public function testGetWithAnnotationsWithScalarAutoloadNamespaces()
    {
        $config = array('annotations' => array(
        	'autoloadNamespaces' => APPLICATION_ROOT . '/library'
        ));
        $this->setExpectedException('Serquant\Validator\Exception\InvalidArgumentException');
        $validator = ValidatorFactory::get($config);
    }

    public function testGetWithUncachedAnnotations()
    {
        $config = array(
            'annotations' => array(
                'autoloadNamespaces' => array(
            		'Symfony\Component\Validator\Constraints' => APPLICATION_ROOT . '/library'
        		)
            )
        );
        $validator = ValidatorFactory::get($config);
        $this->assertInstanceOf('Symfony\Component\Validator\ValidatorInterface', $validator);
    }

    public function testGetWithCachedAnnotationsInProductionEnvironment()
    {
        $config = array(
            'annotations' => array(
                'cache' => 'apc',
                'autoloadNamespaces' => array(
            		'Symfony\Component\Validator\Constraints' => APPLICATION_ROOT . '/library'
        		)
            )
        );
        $validator = ValidatorFactory::get($config);
        $this->assertInstanceOf('Symfony\Component\Validator\ValidatorInterface', $validator);

        $config = array(
            'annotations' => array(
                'cache' => 'apc',
                'debug' => false,
                'autoloadNamespaces' => array(
            		'Symfony\Component\Validator\Constraints' => APPLICATION_ROOT . '/library'
        		)
            )
        );
        $validator = ValidatorFactory::get($config);
        $this->assertInstanceOf('Symfony\Component\Validator\ValidatorInterface', $validator);
    }

    public function testGetWithCachedAnnotationsInTestingEnvironment()
    {
        $config = array(
            'annotations' => array(
                'cache' => 'apc',
                'debug' => true,
                'autoloadNamespaces' => array(
            		'Symfony\Component\Validator\Constraints' => APPLICATION_ROOT . '/library'
        		)
            )
        );
        $validator = ValidatorFactory::get($config);
        $this->assertInstanceOf('Symfony\Component\Validator\ValidatorInterface', $validator);
    }

    public function testGetWithNamespaceAlias()
    {
        $config = array(
            'annotations' => array(
                'namespaceAlias' => array(
                    'Symfony\Component\Validator\Constraints\\' => 'validator'
        		),
                'autoloadNamespaces' => array(
            		'Symfony\Component\Validator\Constraints' => APPLICATION_ROOT . '/library'
        		)
    		)
        );
        $validator = ValidatorFactory::get($config);
        $this->assertInstanceOf('Symfony\Component\Validator\ValidatorInterface', $validator);

    }
}