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
namespace Serquant\Resource\Persistence\Doctrine\Entity;

use Serquant\Converter\Mapping as Converter;

/**
 * @Entity
 * @Table(name="users")
 */
class User
{
    /**
     * @Converter\Property(type="integer")
     * @Id @GeneratedValue(strategy="NONE")
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Converter\Property(type="string")
     * @Column(type="string", length=50)
     * @validation:MaxLength(20)
     */
    public $status;

    /**
     * @Converter\Property(type="string")
     * @Column(type="string", length=255, unique=true)
     */
    public $username;

    /**
     * @Converter\Property(type="string")
     * @Column(type="string", length=255)
     */
    public $name;

    public function __construct() {
    }

    public function getId() {
        return $this->id;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getName() {
        return $this->name;
    }
}

