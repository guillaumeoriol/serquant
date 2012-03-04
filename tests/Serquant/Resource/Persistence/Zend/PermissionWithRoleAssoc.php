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
class PermissionWithRoleAssoc
{
    private $role;

    private $resource;

    public function setRole(RoleWithPermissions $role)
    {
        $this->role = $role;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }
}