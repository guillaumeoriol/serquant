<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Doctrine
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Doctrine\DBAL\Types;

use Doctrine\DBAL\DBALException,
    Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * BLOB type compatible with MySQL.
 *
 * @category Serquant
 * @package  Doctrine
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class BlobType extends \Doctrine\DBAL\Types\Type
{
    /**
     * Name of the Doctrine type, to be used in the "type" attribute of the
     * "column" metadata.
     * @var string
     */
    const BLOB = 'blob';

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration The field declaration.
     * @param AbstractPlatform $platform The currently used database platform.
     * @return string
     */
    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        if (!method_exists($platform, 'getBlobTypeDeclarationSQL')) {
            throw DBALException::notSupported('getBlobTypeDeclarationSQL');
        }

        return $platform->getBlobTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return self::BLOB;
    }
}