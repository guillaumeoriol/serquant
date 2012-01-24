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

    private function setupDatabase()
    {
        $dataSet = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/users.yaml'
        );

        $this->db = $this->getTestAdapter();
        $connection = new \Zend_Test_PHPUnit_Db_Connection($this->db, null);
        $tester = new \Zend_Test_PHPUnit_Db_SimpleTester($connection);
        $tester->setupDatabase($dataSet);
    }

    protected function setUp()
    {
        $this->setupDatabase();
        $this->em = $this->getTestEntityManager();
        $this->persister = new \Serquant\Persistence\Zend($this->em);
    }

    public function testTranslateWithEmptyExpressions()
    {
        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);

        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $expressions = array();

        list ($query, $pageNumber, $pageSize) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertInstanceOf('Zend_Db_Select', $query);
        $this->assertEquals(
        	"SELECT `users`.* FROM `users`",
            $query->__toString()
        );
        $this->assertNull($pageNumber);
        $this->assertNull($pageSize);
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

    public function testTranslateWithLimitOperator()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $expressions = array('limit(30,10)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);

        list ($query, $pageNumber, $pageSize) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertInstanceOf('Zend_Db_Select', $query);
        $this->assertEquals(4, $pageNumber);
        $this->assertEquals(10, $pageSize);
    }

    public function testTranslateWithAlternateComparisonSyntax()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $expressions = array('username' => 'fred', 'status' => 'deprecated');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);

        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertInstanceOf('Zend_Db_Select', $query);
        $this->assertEquals(
        	"SELECT `users`.* FROM `users` WHERE (username = 'fred') AND (status = 'deprecated')",
            $query->__toString()
        );
    }

    public function testTranslateWithAlternateComparisonSyntaxIncludingWildcard()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $expressions = array('username' => 'f*', 'status' => 'deprecated');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);

        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertInstanceOf('Zend_Db_Select', $query);
        $this->assertEquals(
        	"SELECT `users`.* FROM `users` WHERE (username like 'f%') AND (status = 'deprecated')",
            $query->__toString()
        );
    }

    public function testTranslateWithAlternateComparisonSyntaxOnAssociation()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $expressions = array('username' => 'a');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);

        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertInstanceOf('Zend_Db_Select', $query);
        $this->assertEquals(
        	"SELECT `users`.* FROM `users` WHERE (username = 'a')",
            $query->__toString()
        );
    }
}
