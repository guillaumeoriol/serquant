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
namespace Serquant\Resource\Persistence\Zend\Db\Table;

use Doctrine\DBAL\Types\Type;
use Serquant\Persistence\Zend\Db\Table;

/**
 * Table data gateway for the PermissionWithRoleAssoc entity
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class PermissionWithRoleAssoc extends Table
{
    protected $_name = 'permissions';

    protected $_primary = array('role', 'resource');

    protected $_sequence = false;

    protected $entityName = 'Serquant\Resource\Persistence\Zend\PermissionWithRoleAssoc';

    protected $fieldNames = array(
        'role' => 'role',
        'resource' => 'resource'
    );

    protected $columnNames = array(
        'role' => 'role',
        'resource' => 'resource'
    );

    public function loadEntity($entity, array $row)
    {
        $props = $this->getProperties();
        $p = $this->getDatabasePlatform();

        $roleClass = 'Serquant\Resource\Persistence\Zend\RoleWithPermissionAssoc';
        $rolePK = array('id' => $row['role']);
        $role = $this->getPersister()->loaded($roleClass, $rolePK);
        if (!$role) {
            $roleGateway = $this->getPersister()->getTableGateway($roleClass);
            $role = $roleGateway->newProxyInstance($rolePK);
        }
        $props['role']->setValue($entity, $role);

        $props['resource']->setValue($entity,
            Type::getType('integer')->convertToPHPValue($row['resource'], $p));
    }
}