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
 * Table data gateway for the Role entity
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Role extends Table
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

    protected $entityName = 'Serquant\Resource\Persistence\Zend\Role';

    protected $fieldNames = array(
    	'id' => 'id',
    	'name' => 'name'
    );

    protected $columnNames = array(
        'id' => 'id',
        'name' => 'name'
    );
}
