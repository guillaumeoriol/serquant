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

class DoctrineTest extends \Doctrine\Tests\OrmTestCase
{
    private $em;

    protected function setUp()
    {
        $this->em = $this->_getTestEntityManager();
    }

    public function testTranslateWithSelectOperator()
    {
        $entityName = '\Serquant\Test\Model\Doctrine\User';

        $persister = new Doctrine();
        $persister->setEntityManager($this->em);

        $method = new \ReflectionMethod($persister, 'translate');
        $method->setAccessible(true);
        list($query) = $method->invoke($persister, $entityName, array('select(id,name)' => null));
        $dql = $query->getDQL();
        $this->assertEquals("select e.id,e.name from $entityName e", $dql);
    }
}
