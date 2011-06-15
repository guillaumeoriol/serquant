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

class User extends \Serquant\Persistence\Zend\Db\Table
{
    /**
     * Table name
     * @var string
     */
    protected $_name = 'users';

    /**
     * Primary key
     * @var string
     */
    protected $_primary = 'id';

    /**
     * Optional sequence.
     * When false, the table has a natural key. Default is true.
     * @var boolean | string
     */
    protected $_sequence = true;
}
