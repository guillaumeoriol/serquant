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
namespace Serquant\Resource\Persistence\Doctrine\Entity;

/**
 * @Entity
 * @Table(name="users")
 */
class EntityWithApplicationAssignedId
{
    /**
     * @Id @GeneratedValue(strategy="NONE")
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="string", length=255)
     */
    public $name;

    public function __construct() {
    }
}

