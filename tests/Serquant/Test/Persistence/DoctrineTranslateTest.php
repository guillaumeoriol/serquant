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

use Serquant\Persistence\Doctrine;

class DoctrineTranslateTest extends \Doctrine\Tests\OrmTestCase
{
    private $em;

    private $persister;

    protected function setUp()
    {
        $this->em = $this->_getTestEntityManager();
        $this->persister = new Doctrine($this->em);
    }

    public function testTranslateWithEmptyExpressions()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expected = "select e from $entityName e";
        $expressions = array();

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query, $pageNumber, $pageSize) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertInstanceOf('Doctrine\ORM\Query', $query);
        $this->assertEquals($expected, $query->getDQL());
        $this->assertNull($pageNumber);
        $this->assertNull($pageSize);
    }

    public function testTranslateWithUnimplementedOperator()
    {
        $this->setExpectedException('RuntimeException');

        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('aggregate(status,count(*))');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
    }

    public function testTranslateWithUnsupportedSyntax()
    {
        $this->setExpectedException('RuntimeException');

        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('(bar' => 'text|bar=string)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
    }

    public function testTranslateWithSelectOperator()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('select(id,name)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertInstanceOf('Doctrine\ORM\Query', $query);
        $this->assertEquals("select e.id,e.name from $entityName e", $query->getDQL());
    }

    public function testTranslateWithSortAscOperator()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('sort(+name)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertInstanceOf('Doctrine\ORM\Query', $query);
        $this->assertEquals("select e from $entityName e order by e.name ASC", $query->getDQL());
    }

    public function testTranslateWithSortDescOperator()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('sort(-name)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertInstanceOf('Doctrine\ORM\Query', $query);
        $this->assertEquals("select e from $entityName e order by e.name DESC", $query->getDQL());
    }

    public function testTranslateWithLimitOperator()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('limit(30,10)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query, $pageNumber, $pageSize) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertInstanceOf('Doctrine\ORM\Query', $query);
        $this->assertEquals(4, $pageNumber);
        $this->assertEquals(10, $pageSize);
    }

    public function testTranslateWithAlternateComparisonSyntax()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('username' => 'fred', 'status' => 'deprecated');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertInstanceOf('Doctrine\ORM\Query', $query);
        $this->assertEquals("select e from $entityName e where e.username = :username and e.status = :status", $query->getDQL());
        $this->assertEquals(array('username' => 'fred', 'status' => 'deprecated'), $query->getParameters());
    }

    public function testTranslateWithAlternateComparisonSyntaxIncludingWildcard()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('username' => 'f*', 'status' => 'deprecated');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $this->assertInstanceOf('Doctrine\ORM\Query', $query);
        $this->assertEquals("select e from $entityName e where e.username like :username and e.status = :status", $query->getDQL());
        $this->assertEquals(array('username' => 'f%', 'status' => 'deprecated'), $query->getParameters());
    }
}
