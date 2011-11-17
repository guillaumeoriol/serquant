<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Loader
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Test\Converter\Mapping\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Serquant\Converter\Mapping\ClassMetadata;
use Serquant\Converter\Mapping\Loader\AnnotationLoader;
use Serquant\DependencyInjection\Factory\AnnotationReaderFactory;

/**
 * Test class for the AnnotationLoader.
 *
 * @category Serquant
 * @package  Loader
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class AnnotationLoaderTest extends \PHPUnit_Framework_TestCase
{
    protected $reader;

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
    }

    public function testConstructWithoutArgument()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $loader = new AnnotationLoader();
    }

    public function testConstructWithArgumentOfWrongClass()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $loader = new AnnotationLoader(new \stdClass());
    }

    public function testConstruct()
    {
        $loader = new AnnotationLoader(new \Doctrine\Common\Annotations\AnnotationReader());
        $this->assertInstanceOf('Serquant\Converter\Mapping\Loader\AnnotationLoader', $loader);
    }

    public function testLoadClassMetadata()
    {
        $loader = new AnnotationLoader($this->reader);

        $class = 'Serquant\Resource\Converter\UserWithPublicProperties';
        $metadata = new ClassMetadata($class);
        $loader->loadClassMetadata($metadata);

        $conversionProperties = $metadata->getProperties();
        $this->assertEmpty(array_diff(
            array('id', 'status', 'username'),
            array_keys($conversionProperties)
        ));

        $this->assertTrue($metadata->isIdentifier('id'));
        $this->assertFalse($metadata->isIdentifier('username'));

        $this->assertEquals('/rest/user/', $metadata->getIdentifierPrefix());
    }
}