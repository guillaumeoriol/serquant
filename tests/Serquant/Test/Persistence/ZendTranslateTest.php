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

class ZendTranslateTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;

    private $em;

    private $persister;

    protected function setUp()
    {
        $this->db = $this->getTestAdapter();
        \Zend_Db_Table::setDefaultAdapter($this->db);
        $this->em = $this->getTestEntityManager();
        $this->persister = new \Serquant\Persistence\Zend($this->em);
    }

    public function testTranslateWithUnimplementedOperator()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $expressions = array('aggregate(status,count(*))');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);

        $this->setExpectedException('RuntimeException');
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
    }

    public function testTranslateWithUnsupportedSyntax()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $expressions = array('(bar' => 'text|bar=string)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);

        $this->setExpectedException('RuntimeException');
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
    }

    public function testTranslateWithSelectOperator()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);

        list ($query) = $method->invoke($this->persister, $entityName, array('select(id,name)'));
        $sql = $query->__toString();
        $this->assertEquals("SELECT `users`.`id`, `users`.`name` FROM `users`", $sql);
    }

    public function testTranslateWithSortOperatorWithoutOrder()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $expressions = array('sort(name)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);

        // As the first character will be removed, "name" will be changed to
        // "ame" that is undefined in the column name array, triggering a
        // notice. This notice will be converted by PHPUnit to an exception.
        $this->setExpectedException('PHPUnit_Framework_Error_Notice');
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
    }

    public function testTranslateWithSortAscOperator()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $expressions = array('sort(+name)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);

        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertEquals("SELECT `users`.* FROM `users` ORDER BY `name` ASC", (string)$query);
    }

    public function testTranslateWithSortDescOperator()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $expressions = array('sort(-name)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);

        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertEquals("SELECT `users`.* FROM `users` ORDER BY `name` DESC", (string)$query);
    }
}
