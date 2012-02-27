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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Events;
use Serquant\DependencyInjection\Factory\AnnotationReaderFactory;
use Serquant\DependencyInjection\Factory\EntityManagerFactory;

/**
 * Test class for the EntityManagerFactory.
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class EntityManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $mappingPath;

    private $proxyDir;

    protected function setUp()
    {
        $this->mappingPath = TEST_PATH . '/Serquant/Resource/Persistence/Doctrine/Entity';
        $this->proxyDir = TEST_PATH . '/Serquant/Resource/Persistence/Doctrine/Proxy';
    }

    public function testGetWithoutMetadataConfig()
    {
        $config = array();
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
            'Doctrine metadata configuration is undefined.'
        );
        $em = EntityManagerFactory::get($config);
    }

    public function testGetWithInvalidMetadataConfig()
    {
        $config = array('metadata' => true);
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
            'Doctrine metadata configuration is undefined.'
        );
        $em = EntityManagerFactory::get($config);
    }

    public function testGetWithoutMetadataDriverConfig()
    {
        $config = array(
        	'metadata' => array(
        		'mappingPaths' => null
            )
        );
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
        	'Either \'mappingPaths\' or \'driver\' metadata is undefined in Doctrine metadata configuration.'
        );
        $em = EntityManagerFactory::get($config);
    }

    public function testGetWithoutProxyConfig()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => $this->mappingPath,
                'driver' => 'annotation'
            )
        );
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
            'Doctrine proxy configuration is undefined.'
        );
        $em = EntityManagerFactory::get($config);
    }

    public function testGetWithInvalidProxyConfig()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => $this->mappingPath,
                'driver' => 'annotation'
            ),
            'proxy' => true
        );
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
            'Doctrine proxy configuration is undefined.'
        );
        $em = EntityManagerFactory::get($config);
    }

    public function testGetWithoutProxyDirConfig()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => $this->mappingPath,
                'driver' => 'annotation'
            ),
            'proxy' => array(
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            )
        );
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
            'Either directory or namespace option is undefined in Doctrine proxy configuration.'
        );
        $em = EntityManagerFactory::get($config);
    }

    public function testGetWithoutConnectionConfig()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => $this->mappingPath,
                'driver' => 'annotation'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
            	'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            )
        );
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
            'Doctrine connection configuration is undefined.'
        );
        $em = EntityManagerFactory::get($config);
    }

    public function testGetWithoutConnectionParamsConfig()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => $this->mappingPath,
                'driver' => 'annotation'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
            	'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'PDO_MYSQL'
        );
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
            'Doctrine connection configuration is undefined.'
        );
        $em = EntityManagerFactory::get($config);
    }

    public function testInitMetadataWithArrayCache()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation',
                'cache' => 'array'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $mf = $em->getMetadataFactory();
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $mf->getCacheDriver());
    }

    public function testInitMetadataWithApcCache()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation',
                'cache' => 'apc'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $mf = $em->getMetadataFactory();
        $this->assertInstanceOf('Doctrine\Common\Cache\ApcCache', $mf->getCacheDriver());
    }

    public function testInitMetadataWithXcacheCache()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation',
                'cache' => 'xcache'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $mf = $em->getMetadataFactory();
        $this->assertInstanceOf('Doctrine\Common\Cache\XcacheCache', $mf->getCacheDriver());
    }

    public function testInitMetadataWithMemcacheCache()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation',
                'cache' => 'memcache'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $mf = $em->getMetadataFactory();
        $this->assertInstanceOf('Doctrine\Common\Cache\MemcacheCache', $mf->getCacheDriver());
    }

    public function testInitMetadataWithInvalidCache()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation',
                'cache' => 'invalid'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
            'Invalid cache driver specified: invalid'
        );
        $em = EntityManagerFactory::get($config);
    }

    public function testInitMetadataWithInvalidDriver()
    {
        AnnotationRegistry::reset();
        $reader = new \stdClass();

        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'invalid'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
        	'Invalid Doctrine metadata driver: invalid'
    	);
        $em = EntityManagerFactory::get($config);
    }

    public function testInitMetadataWithInvalidAnnotationReader()
    {
        AnnotationRegistry::reset();
        $reader = new \stdClass();

        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation',
                'annotationReader' => $reader
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
        	'The given annotation reader does not implement Doctrine\Common\Annotations\Reader interface.'
    	);
        $em = EntityManagerFactory::get($config);
    }

    public function testInitMetadataWithAnnotationReader()
    {
        AnnotationRegistry::reset();
        $reader = AnnotationReaderFactory::get(array(
        	'annotationFile' => TEST_PATH . '/library/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
        ));

        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation',
                'annotationReader' => $reader
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $driver = $em->getConfiguration()->getMetadataDriverImpl();
        $this->assertInstanceOf('Doctrine\ORM\Mapping\Driver\AnnotationDriver', $driver);
    }

    public function testInitMetadataWithXmlDriver()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'xml'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $driver = $em->getConfiguration()->getMetadataDriverImpl();
        $this->assertInstanceOf('Doctrine\ORM\Mapping\Driver\XmlDriver', $driver);
    }

    public function testInitMetadataWithYamlDriver()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'yaml'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $driver = $em->getConfiguration()->getMetadataDriverImpl();
        $this->assertInstanceOf('Doctrine\ORM\Mapping\Driver\YamlDriver', $driver);
    }

    public function testInitMetadataWithInvalidQueryCache()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'query' => array(
                'cache' => 'invalid'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
            'Invalid cache driver specified: invalid'
        );
        $em = EntityManagerFactory::get($config);
    }

    public function testInitMetadataWithQueryCache()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'query' => array(
                'cache' => 'array'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $cache = $em->getConfiguration()->getQueryCacheImpl();
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $cache);
    }

    public function testInitProxyWithAutoGenerating()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'query' => array(
                'cache' => 'array'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy',
                'autogenerate' => true
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $this->assertTrue($em->getConfiguration()->getAutoGenerateProxyClasses());
    }

    public function testGetConnectionWithAdapter()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'query' => array(
                'cache' => 'array'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy',
                'autogenerate' => true
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $this->assertInstanceOf(
            'Doctrine\DBAL\Driver\PDOMySql\Driver',
            $em->getConnection()->getDriver()
        );
    }

    public function testGetConnectionWithAdapterClass()
    {
        $className = 'Serquant\Doctrine\DBAL\Driver\PDOMySql\Driver';
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'query' => array(
                'cache' => 'array'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy',
                'autogenerate' => true
            ),
            'adapterClass' => $className,
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $this->assertInstanceOf(
            $className,
            $em->getConnection()->getDriver()
        );
    }

    public function testGetConnectionWithCharset()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'query' => array(
                'cache' => 'array'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy',
                'autogenerate' => true
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname',
                'charset' => 'utf8'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
    }

    public function testInitLogger()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            ),
            'log' => array(
                array(
                    'writerName' => 'Mock'
                )
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $this->assertInstanceOf('Serquant\Doctrine\Logger', $em->getConfiguration()->getSQLLogger());
    }

    public function testInitTypeWithInvalidType()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            ),
            'type' => 'Serquant\Doctrine\DBAL\Types\BlobType'
        );
        $this->setExpectedException(
        	'Serquant\DependencyInjection\Exception\InvalidArgumentException',
        	'Doctrine custom types shall be an array whose key is the name used in field metadata and value is the corresponding fully qualified class name.'
		);
        $em = EntityManagerFactory::get($config);
    }

    public function testInitTypeWith()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            ),
            'type' => array(
            	'blob' => 'Serquant\Doctrine\DBAL\Types\BlobType'
        	)
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $this->assertTrue(\Doctrine\DBAL\Types\Type::hasType('blob'));
    }

    public function testInitEventManagerWithoutEventManager()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            )
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $this->assertNotNull($em->getEventManager());
    }

    public function testInitEventManagerWithArgumentOfWrongType()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            ),
            'eventManager' => 'dummy'
        );
        $this->setExpectedException('Serquant\DependencyInjection\Exception\InvalidArgumentException', null, 50);
        $em = EntityManagerFactory::get($config);
    }

    public function testInitEventManagerWithArgumentOfWrongClass()
    {
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            ),
            'eventManager' => new \stdClass()
        );
        $this->setExpectedException('Serquant\DependencyInjection\Exception\InvalidArgumentException', null, 50);
        $em = EntityManagerFactory::get($config);
    }

    public function testInitEventManager()
    {
        $evm = new \Doctrine\Common\EventManager();
        $config = array(
            'metadata' => array(
                'mappingPaths' => '/dummy/path',
                'driver' => 'annotation'
            ),
            'proxy' => array(
                'directory' => $this->proxyDir,
                'namespace' => 'Serquant\Resource\Persistence\Doctrine\Proxy'
            ),
            'adapter' => 'pdo_mysql',
            'params' => array(
                'host' => 'localhost',
                'user' => 'user',
                'password' => 'password',
                'dbname' => 'dbname'
            ),
            'eventManager' => $evm
        );
        $em = EntityManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
        $this->assertSame($evm, $em->getEventManager());
    }
}