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
namespace Serquant\Test\Doctrine\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Serquant\Doctrine\DBAL\Platforms\MySqlPlatform;
use Serquant\Doctrine\DBAL\Types\BlobType;

/**
 * Test class for the BlobType
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class BlobTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testGetName()
    {
        $type = Type::getType('blob');
        $this->assertEquals(BlobType::BLOB, $type->getName());
    }

    public function testGetSqlDeclaration()
    {
        $platform = new MySqlPlatform();
        $type = Type::getType('blob');
        $field = array('length' => 100);
        $this->assertEquals('TINYBLOB', $type->getSqlDeclaration($field, $platform));
    }

    public function testGetSqlDeclarationOnWrongPlatform()
    {
        $platform = new \Doctrine\DBAL\Platforms\MySqlPlatform();
        $type = Type::getType('blob');
        $field = array('length' => 100);
        $this->setExpectedException('Doctrine\DBAL\DBALException');
        $type->getSqlDeclaration($field, $platform);
    }
}