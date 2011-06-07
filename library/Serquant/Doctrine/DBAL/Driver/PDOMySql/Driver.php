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
namespace Serquant\Doctrine\DBAL\Driver\PDOMySql;

/**
 * Extension of the original PDOMySql driver to be able to extend
 * the native Doctrine MySqlPlatform.
 *
 * @category Serquant
 * @package  Doctrine
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Driver extends \Doctrine\DBAL\Driver\PDOMySql\Driver
{
    /**
     * Get database platform
     *
     * @return \Serquant\Doctrine\DBAL\Platforms\MySqlPlatform
     */
    public function getDatabasePlatform()
    {
        return new \Serquant\Doctrine\DBAL\Platforms\MySqlPlatform();
    }
}