<?php
/**
 * This file is part of the application.
 *
 * PHP version 5.3
 *
 * @category Domain
 * @package  Entity
 * @author   Guillaume Oriol <goriol@alterimago.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://erp.alterimago.com/
 */
namespace Serquant\Resource\Persistence\Zend;

/**
 * Use case of entity having a different name in the domain and the database
 * for its identifier
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class RoleWithInflection
{
    private $roleId;

    private $name;

    /**
     * Get role identifier
     *
     * @return integer
     */
    public function getRoleId()
    {
        return $this->roleId;
    }

    /**
     * Set role name
     *
     * @param string $name Role name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get role name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}