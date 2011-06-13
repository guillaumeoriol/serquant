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
namespace Serquant\Test\Persistence;

use Serquant\Persistence\Zend\Db\Table;

class ZendTest extends \PHPUnit_Framework_TestCase
{
    private $db;

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

    protected function setUp()
    {
        $this->db = $this->getTestAdapter();
    }

    public function testTranslateWithSelectOperator()
    {
        $entityName = '\Serquant\Test\Model\Zend\User';

        \Zend_Db_Table::setDefaultAdapter($this->db);
        $persister = new \Serquant\Persistence\Zend();
        $method = new \ReflectionMethod($persister, 'getTable');
        $method->setAccessible(true);
        $table = $method->invoke($persister, $entityName);

        $method = new \ReflectionMethod($table, 'translate');
        $method->setAccessible(true);
        list($query) = $method->invoke($table, array('select(id,name)' => null));
        $sql = $query->__toString();
        $this->assertEquals("SELECT `users`.`id`, `users`.`name` FROM `users`", $sql);
    }
}
