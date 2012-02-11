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
namespace Serquant\Test\Persistence\Zend\Db;

/**
 * Test class for Table#translate
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class TableTranslateTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;

    private function setupDatabase()
    {
        $dataSet = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            realpath(dirname(__FILE__) . '/../../fixture/users.yaml')
        );

        $this->db = $this->getTestAdapter();
        $connection = new \Zend_Test_PHPUnit_Db_Connection($this->db, null);
        $tester = new \Zend_Test_PHPUnit_Db_SimpleTester($connection);
        $tester->setupDatabase($dataSet);
    }

    protected function setUp()
    {
        $this->setupDatabase();
    }

    public function testTranslateWithEmptyExpressions()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $expressions = array();
        list ($query, $pageNumber, $pageSize) = $gateway->translate($expressions);
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
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $expressions = array('aggregate(status,count(*))');

        $this->setExpectedException('RuntimeException', null, 30);
        list ($query) = $gateway->translate($expressions);
    }

    public function testTranslateWithUnsupportedSyntax()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $expressions = array('(bar' => 'text|bar=string)');

        $this->setExpectedException('RuntimeException', null, 31);
        list ($query) = $gateway->translate($expressions);
    }

    public function testTranslateWithSortOperatorHavingTooShortOperand()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $expressions = array('sort(-,+name)');

        list ($query) = $gateway->translate($expressions);
        $this->assertEquals("SELECT `users`.* FROM `users` ORDER BY `name` ASC", (string) $query);
    }

    public function testTranslateWithSortOperatorWithoutOrder()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $expressions = array('sort(name,+status)');

        // As the first character will be removed, "name" will be changed to
        // "ame" that is unknown in the search field map and thus will be
        // discarded. Only "status" will be kept.
        $this->setExpectedException('PHPUnit_Framework_Error_Notice');
        $this->assertEquals("SELECT `users`.* FROM `users` ORDER BY `status` ASC", (string) $query);
        list ($query) = $gateway->translate($expressions);
    }

    public function testTranslateWithSortAscOperator()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $expressions = array('sort(+name)');

        list ($query) = $gateway->translate($expressions);
        $this->assertEquals("SELECT `users`.* FROM `users` ORDER BY `name` ASC", (string) $query);
    }

    public function testTranslateWithSortDescOperator()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $expressions = array('sort(-name)');

        list ($query) = $gateway->translate($expressions);
        $this->assertEquals("SELECT `users`.* FROM `users` ORDER BY `name` DESC", (string) $query);
    }

    public function testTranslateWithSortOperatorHavingMultipleOperands()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $expressions = array('sort(-name,+status)');

        list ($query) = $gateway->translate($expressions);
        $this->assertEquals("SELECT `users`.* FROM `users` ORDER BY `name` DESC, `status` ASC", (string) $query);
    }

    public function testTranslateWithLimitOperator()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $expressions = array('limit(30,10)');

        list ($query, $pageNumber, $pageSize) = $gateway->translate($expressions);
        $this->assertInstanceOf('Zend_Db_Select', $query);
        $this->assertEquals(4, $pageNumber);
        $this->assertEquals(10, $pageSize);
    }

    public function testTranslateWithAlternateComparisonSyntax()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $expressions = array('username' => 'fred', 'status' => 'deprecated');

        list ($query) = $gateway->translate($expressions);
        $this->assertInstanceOf('Zend_Db_Select', $query);
        $this->assertEquals(
        	"SELECT `users`.* FROM `users` WHERE (username = 'fred') AND (status = 'deprecated')",
            (string) $query
        );
    }

    public function testTranslateWithAlternateComparisonSyntaxIncludingWildcard()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $expressions = array('username' => 'f*', 'status' => 'deprecated');

        list ($query) = $gateway->translate($expressions);
        $this->assertInstanceOf('Zend_Db_Select', $query);
        $this->assertEquals(
        	"SELECT `users`.* FROM `users` WHERE (username like 'f%') AND (status = 'deprecated')",
            (string) $query
        );
    }

    public function testTranslateWithAlternateComparisonSyntaxOnAssociation()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Issue;
        $expressions = array('lastname' => 'Dupont');

        list ($query) = $gateway->translate($expressions);
        $this->assertInstanceOf('Zend_Db_Select', $query);
        $this->assertEquals(
        	"SELECT `i`.*, `p`.* FROM `issues` AS `i`
 LEFT JOIN `people` AS `p` ON i.person_id = p.id WHERE (p.last_name = 'Dupont')",
            (string) $query
        );
    }
}