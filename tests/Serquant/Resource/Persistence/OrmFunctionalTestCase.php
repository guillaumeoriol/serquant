<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Resource\Persistence;

/**
 * Test environment for persistence.
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
abstract class OrmFunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Create an instance of a Zend adapter and return it.
     *
     * @return void
     */
    protected function getTestAdapter()
    {
        $constants = array(
            'host'     => UNIT_TESTS_DB_HOST,
            'username' => UNIT_TESTS_DB_USERNAME,
            'password' => UNIT_TESTS_DB_PASSWORD,
            'dbname'   => UNIT_TESTS_DB_DBNAME,
            'port'     => UNIT_TESTS_DB_PORT
        );
        $adapter = \Zend_Db::factory(UNIT_TESTS_DB_ADAPTER, $constants);
        \Zend_Db_Table::setDefaultAdapter($adapter);
        return $adapter;
    }

    /**
     * Creates an EntityManager for testing purposes.
     *
     * @return Doctrine\ORM\EntityManager
     */
    protected function getTestEntityManager()
    {
        $path = TEST_PATH . '/Serquant/Resource/Persistence/Doctrine';
        $cache = new \Doctrine\Common\Cache\ArrayCache;

        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl($cache);
        $driverImpl = $config->newDefaultAnnotationDriver($path . '/Entity');
        $config->setMetadataDriverImpl($driverImpl);
        $config->setQueryCacheImpl($cache);
        $config->setProxyDir($path . '/Proxy');
        $config->setProxyNamespace('Serquant\Resource\Persistence\Doctrine\Proxy');
        $config->setAutoGenerateProxyClasses(true);

        $connection = array(
            'driver'   => UNIT_TESTS_DB_ADAPTER,
            'host'     => UNIT_TESTS_DB_HOST,
            'user'     => UNIT_TESTS_DB_USERNAME,
            'password' => UNIT_TESTS_DB_PASSWORD,
            'dbname'   => UNIT_TESTS_DB_DBNAME,
            'port'     => UNIT_TESTS_DB_PORT
        );
        return \Doctrine\ORM\EntityManager::create($connection, $config);
    }
}