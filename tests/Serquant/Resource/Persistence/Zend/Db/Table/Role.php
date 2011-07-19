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
namespace Serquant\Resource\Persistence\Zend\Db\Table;

class Role extends \Zend_Db_Table_Abstract
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
}
