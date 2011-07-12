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
        $this->persister = new Doctrine();
        $this->persister->setEntityManager($this->em);
    }

    public function testTranslateWithUnimplementedOperator()
    {
        $this->setExpectedException('RuntimeException');

        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('aggregate(status,count(*))');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
    }

    public function testTranslateWithUnsupportedSyntax()
    {
        $this->setExpectedException('RuntimeException');

        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('(bar' => 'text|bar=string)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
    }

    public function testTranslateWithSelectOperator()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('select(id,name)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $dql = $query->getDQL();
        $this->assertEquals("select e.id,e.name from $entityName e", $dql);
    }

    public function testTranslateWithSortOperatorWithoutOrder()
    {
        $this->setExpectedException('RuntimeException');

        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('sort(name)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
    }

    public function testTranslateWithSortAscOperator()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('sort(+name)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $dql = $query->getDQL();
        $this->assertEquals("select e from $entityName e order by e.name ASC", $dql);
    }

    public function testTranslateWithSortDescOperator()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expressions = array('sort(-name)');

        $method = new \ReflectionMethod($this->persister, 'translate');
        $method->setAccessible(true);
        list ($query) = $method->invoke($this->persister, $entityName, $expressions);
        $dql = $query->getDQL();
        $this->assertEquals("select e from $entityName e order by e.name DESC", $dql);
    }
}
