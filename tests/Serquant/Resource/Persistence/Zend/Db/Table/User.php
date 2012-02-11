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
 * Table data gateway for the User entity
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class User extends Table
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

    protected $entityName = 'Serquant\Resource\Persistence\Zend\User';

    protected $fieldNames = array(
        'id' => 'id',
        'status' => 'status',
        'name' => 'name',
        'username' => 'username'
    );

    protected $columnNames = array(
        'id' => 'id',
        'status' => 'status',
        'name' => 'name',
        'username' => 'username'
    );

    public function loadEntity(array $row)
    {
        $entity = $this->newInstance();

        $props = $this->getProperties();
        $p = $this->getDatabasePlatform();

        $props['id']->setValue($entity,
            Type::getType('integer')->convertToPHPValue($row['id'], $p));
        $props['status']->setValue($entity,
            Type::getType('string')->convertToPHPValue($row['status'], $p));
        $props['name']->setValue($entity,
            Type::getType('string')->convertToPHPValue($row['name'], $p));
        $props['username']->setValue($entity,
            Type::getType('string')->convertToPHPValue($row['username'], $p));

        return $entity;
    }
}
