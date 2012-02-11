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
 * Use case of a table having no database assigned key (_sequence is false).
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Permission extends Table
{
    protected $_name = 'permissions';

    protected $_primary = array('role', 'resource');

    protected $_sequence = false;

    protected $entityName = 'Serquant\Resource\Persistence\Zend\Permission';

    protected $fieldNames = array(
        'role' => 'role',
        'resource' => 'resource'
    );

    protected $columnNames = array(
        'role' => 'role',
        'resource' => 'resource'
    );
}