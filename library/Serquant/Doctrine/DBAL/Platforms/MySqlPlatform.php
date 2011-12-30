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
namespace Serquant\Doctrine\DBAL\Platforms;

/**
 * Extension of the original platform to get the proper conversion between
 * database type and Doctrine type for BLOB.
 * Extension of the original Doctrine MySqlPlatform to make the new BLOB type
 * platform-dependent and to avoid getting exception when migrating
 * database schema.
 *
 * @category Serquant
 * @package  Doctrine
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class MySqlPlatform extends \Doctrine\DBAL\Platforms\MySqlPlatform
{
    /**
     * Get MySQL type declaration matching Doctrine type
     *
     * @param array $field Field declaration
     * @return string
     */
    public function getBlobTypeDeclarationSQL(array $field)
    {
        if (!empty($field['length']) && is_numeric($field['length'])) {
            $length = $field['length'];
            if ($length < 256) {
                return 'TINYBLOB';
            } else if ($length < 65536) {
                return 'BLOB';
            } else if ($length < 16777216) {
                return 'MEDIUMBLOB';
            }
        }
        return 'LONGBLOB';
    }

    /**
     * Add a mapping from MySQL BLOB type flavors to the custom 'blob'
     * Doctrine type.
     *
     * @return void
     */
    protected function initializeDoctrineTypeMappings()
    {
        parent::initializeDoctrineTypeMappings();

        $this->doctrineTypeMapping['tinyblob'] = 'blob';
        $this->doctrineTypeMapping['blob'] = 'blob';
        $this->doctrineTypeMapping['mediumblob'] = 'blob';
        $this->doctrineTypeMapping['longblob'] = 'blob';
    }
}