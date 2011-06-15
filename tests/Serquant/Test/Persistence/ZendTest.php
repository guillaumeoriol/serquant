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

class ZendTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;

    private $persister;

    protected function setUp()
    {
        $this->db = $this->getTestAdapter();
        \Zend_Db_Table::setDefaultAdapter($this->db);
        $this->persister = new \Serquant\Persistence\Zend();
    }

    public function testTranslateWithUnimplementedOperator()
    {
        $this->setExpectedException('RuntimeException');

        $entityName = '\Serquant\Resource\Persistence\Zend\User';
        $expressions = array('aggregate(status,count(*))');

        $method = new \ReflectionMethod($this->persister, 'getTable');
        $method->setAccessible(true);
        $table = $method->invoke($this->persister, $entityName);

        $method = new \ReflectionMethod($table, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($table, $expressions);
    }

    public function testTranslateWithSelectOperator()
    {
        $entityName = '\Serquant\Resource\Persistence\Zend\User';

        $method = new \ReflectionMethod($this->persister, 'getTable');
        $method->setAccessible(true);
        $table = $method->invoke($this->persister, $entityName);

        $method = new \ReflectionMethod($table, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($table, array('select(id,name)'));
        $sql = $query->__toString();
        $this->assertEquals("SELECT `users`.`id`, `users`.`name` FROM `users`", $sql);
    }
}
