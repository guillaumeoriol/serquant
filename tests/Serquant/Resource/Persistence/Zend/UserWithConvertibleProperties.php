<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Resource\Persistence\Zend;

/**
 * Entity written to test convertToDatabaseValues
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 * @Entity
 */
class UserWithConvertibleProperties
{
    /**
     * @Column(type="integer", name="identifier")
     * @Id
     */
    public $id;

    /**
     * @Column(type="boolean", name="is_active")
     */
    public $active;

    /**
     * @Column(type="date", name="created_on")
     */
    public $createdOn;
}