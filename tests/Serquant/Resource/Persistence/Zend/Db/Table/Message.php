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

class Message extends \Zend_Db_Table_Abstract
{
    /**
     * Table name
     * @var string
     */
    protected $_name = 'messages';

    /**
     * Primary key
     * @var array
     */
    protected $_primary = array('language', 'key');

    /**
     * Optional sequence.
     * When false, the table has a natural key. Default is true.
     * @var boolean | string
     */
    protected $_sequence = false;
}
