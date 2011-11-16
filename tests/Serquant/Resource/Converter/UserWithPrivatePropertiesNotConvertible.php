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
namespace Serquant\Resource\Converter;

use Serquant\Converter\Mapping as Converter;

/**
 * @Entity
 */
class UserWithPrivatePropertiesNotConvertible
{
    /**
     * @Converter\Property(type="integer")
     */
    private $id;

    /**
     * @Converter\Property(type="bool")
     */
    private $status;

    /**
     * @Converter\Property(type="string")
     */
    private $username;

    /**
     * @Converter\Property(type="Serquant\Resource\Converter\RoleWithPrivateProperties")
     */
    private $role;

    public function __construct() {
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setRole($role) {
        $this->role = $role;
    }

    public function getRole() {
        return $this->role;
    }
}

