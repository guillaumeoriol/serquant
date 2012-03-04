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
 * Use case of entities implementing a bidirectional one-to-many association.
 * This is a special case of cyclic references between domain object.
 *
 * RoleWithPermissionAssoc (inverse side)
 * - id
 * - name
 * - permissions: Permission [*]
 *
 * PermissionWithRoleAssoc (owning side)
 * - role: RoleWithPermissions [1]
 * - resource
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class RoleWithPermissionAssoc
{
    private $id;

    private $name;

    private $permissions;

    /**
     * Get role id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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

    public function getPermissions()
    {
        return $this->permissions;
    }
}