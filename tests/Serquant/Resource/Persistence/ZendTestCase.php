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
 * Test environment for Zend persister.
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
abstract class ZendTestCase extends \PHPUnit_Framework_TestCase
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
        return $adapter;
    }
}