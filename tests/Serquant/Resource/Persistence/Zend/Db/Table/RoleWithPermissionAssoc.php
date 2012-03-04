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
 * Table data gateway for the RoleWithPermissionAssoc entity
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class RoleWithPermissionAssoc extends Table
{
    /**
     * Table name
     * @var string
     */
    protected $_name = 'roles';

    /**
     * Primary key
     * @var string
     */
    protected $_primary = 'id';

    protected $entityName = 'Serquant\Resource\Persistence\Zend\RoleWithPermissionAssoc';

    protected $fieldNames = array(
    	'id' => 'id',
    	'name' => 'name'
    );

    protected $columnNames = array(
        'id' => 'id',
        'name' => 'name'
    );

    public function loadEntity($entity, array $row)
    {
        $props = $this->getProperties();
        $p = $this->getDatabasePlatform();

        $props['id']->setValue($entity,
            Type::getType('integer')->convertToPHPValue($row['id'], $p));

        $props['name']->setValue($entity,
            Type::getType('string')->convertToPHPValue($row['name'], $p));

        // Retrieve related permissions
        $permissions = $this->getPersister()->fetchAll(
        	'Serquant\Resource\Persistence\Zend\PermissionWithRoleAssoc',
            array('role' => $row['id'])
        );
        $props['permissions']->setValue($entity, $permissions);
    }
}
