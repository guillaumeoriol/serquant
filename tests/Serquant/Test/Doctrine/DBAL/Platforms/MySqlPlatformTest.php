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
namespace Serquant\Test\Doctrine\DBAL\Platforms;

use Serquant\Doctrine\DBAL\Platforms\MySqlPlatform;

/**
 * Test class for the MySqlPlatform
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class MySqlPlatformTest extends \PHPUnit_Framework_TestCase
{
    public function testGetBlobTypeDeclarationSQLWithoutLength()
    {
        $field = array();
        $platform = new MySqlPlatform();
        $declaration = $platform->getBlobTypeDeclarationSQL($field);
        $this->assertEquals('LONGBLOB', $declaration);
    }

    public function testGetBlobTypeDeclarationSQLWithNotNumericLength()
    {
        $field = array(
            'length' => 'not numeric'
        );
        $platform = new MySqlPlatform();
        $declaration = $platform->getBlobTypeDeclarationSQL($field);
        $this->assertEquals('LONGBLOB', $declaration);
    }

    public function testGetBlobTypeDeclarationSQLWithZeroLength()
    {
        $field = array(
            'length' => 0
        );
        $platform = new MySqlPlatform();
        $declaration = $platform->getBlobTypeDeclarationSQL($field);
        $this->assertEquals('LONGBLOB', $declaration);
    }

    public function testGetBlobTypeDeclarationSQLWith8BitsLength()
    {
        $field = array(
            'length' => pow(2, 8) - 1
        );
        $platform = new MySqlPlatform();
        $declaration = $platform->getBlobTypeDeclarationSQL($field);
        $this->assertEquals('TINYBLOB', $declaration, $field['length']);
    }

    public function testGetBlobTypeDeclarationSQLWith16BitsLength()
    {
        $field = array(
            'length' => pow(2, 16) - 1
        );
        $platform = new MySqlPlatform();
        $declaration = $platform->getBlobTypeDeclarationSQL($field);
        $this->assertEquals('BLOB', $declaration, $field['length']);
    }

    public function testGetBlobTypeDeclarationSQLWith24BitsLength()
    {
        $field = array(
            'length' => pow(2, 24) - 1
        );
        $platform = new MySqlPlatform();
        $declaration = $platform->getBlobTypeDeclarationSQL($field);
        $this->assertEquals('MEDIUMBLOB', $declaration, $field['length']);
    }

    public function testGetBlobTypeDeclarationSQLWith32BitsLength()
    {
        $field = array(
            'length' => pow(2, 32) - 1
        );
        $platform = new MySqlPlatform();
        $declaration = $platform->getBlobTypeDeclarationSQL($field);
        $this->assertEquals('LONGBLOB', $declaration, $field['length']);
    }

    public function testInitializeDoctrineTypeMappings()
    {
        $expected = array(
            'tinyblob'   => 'blob',
            'blob'       => 'blob',
            'mediumblob' => 'blob',
            'longblob'   => 'blob'
        );

        $platform = new MySqlPlatform();

        $method = new \ReflectionMethod($platform, 'initializeDoctrineTypeMappings');
        $method->setAccessible(true);
        $method->invoke($platform);

        $property = new \ReflectionProperty($platform, 'doctrineTypeMapping');
        $property->setAccessible(true);
        $actual = $property->getValue($platform);

        $this->assertEquals($expected, array_intersect_assoc($expected, $actual));
    }
}