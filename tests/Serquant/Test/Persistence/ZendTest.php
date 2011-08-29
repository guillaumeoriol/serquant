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

    private $em;

    private $persister;

    protected function setUp()
    {
        $this->db = $this->getTestAdapter();
        \Zend_Db_Table::setDefaultAdapter($this->db);
        $this->em = $this->getTestEntityManager();
        $this->persister = new \Serquant\Persistence\Zend($this->em);
    }

    public function testNormalizeEntityName()
    {
        $method = new \ReflectionMethod($this->persister, 'normalizeEntityName');
        $method->setAccessible(true);

        $name = 'Serquant\Resource\Persistence\Zend\User';
        $result = $method->invoke($this->persister, $name);
        $this->assertEquals($name, $result);

        $object = new $name;
        $result = $method->invoke($this->persister, $object);
        $this->assertEquals($name, $result);

        $this->setExpectedException('InvalidArgumentException');
        $result = $method->invoke($this->persister, 1);
    }
}