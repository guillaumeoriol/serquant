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

use Serquant\Persistence\Zend\Db\Table;

/**
 * Table data gateway for the Permission entity
 *
 * Use case of a table having a compound key with different names in object
 * domain and relational database
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class PermissionWithInflection extends Table
{
    protected $_name = 'permissions';

    protected $_primary = array('role', 'resource');

    protected $_sequence = false;

    protected $entityName = 'Serquant\Resource\Persistence\Zend\Permission';

    protected $fieldNames = array(
        'role' => 'roleId',
        'resource' => 'resourceId'
    );

    protected $columnNames = array(
        'roleId' => 'role',
        'resourceId' => 'resource'
    );
}