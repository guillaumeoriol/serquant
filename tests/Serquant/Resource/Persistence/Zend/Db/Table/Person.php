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
 * Table data gateway for the Person entity
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Person extends Table
{
    /**
     * Table name
     * @var string
     */
    protected $_name = 'people';

    /**
     * Primary key
     * @var string
     */
    protected $_primary = 'id';

    protected $entityName = 'Serquant\Resource\Persistence\Zend\Person';

    protected $columnNames = array(
        'id' => 'id',
        'firstName' => 'first_name',
        'lastName' => 'last_name'
    );

    protected $fieldNames = array(
    	'id' => 'id',
    	'first_name' => 'firstName',
    	'last_name' => 'lastName'
    );

    public function loadEntity($entity, array $row)
    {
        $props = $this->getProperties();
        $p = $this->getDatabasePlatform();

        $props['id']->setValue($entity,
            Type::getType('integer')->convertToPHPValue($row['id'], $p));
        $props['firstName']->setValue($entity,
            Type::getType('string')->convertToPHPValue($row['first_name'], $p));
        $props['lastName']->setValue($entity,
            Type::getType('string')->convertToPHPValue($row['last_name'], $p));
    }

    public function loadRow($entity)
    {
        $row = array();
        $p = $this->getDatabasePlatform();

        $row['id'] = Type::getType('integer')->convertToDatabaseValue(
            $entity->getId(), $p);
        $row['first_name'] = Type::getType('integer')->convertToDatabaseValue(
            $entity->getFirstName(), $p);
        $row['last_name'] = Type::getType('integer')->convertToDatabaseValue(
            $entity->getLastName(), $p);

        return $row;
    }

    public function computeChangeSet($old, $new)
    {
        $row = array();
        $p = $this->getDatabasePlatform();

        if ($old->getId() !== ($v = $new->getId())) {
            $row['id'] = Type::getType('integer')->convertToDatabaseValue($v, $p);
        }
        if ($old->getFirstName() !== ($v = $new->getFirstName())) {
            $row['first_name'] = Type::getType('integer')->convertToDatabaseValue($v, $p);
        }
        if ($old->getLastName() !== ($v = $new->getLastName())) {
            $row['last_name'] = Type::getType('integer')->convertToDatabaseValue($v, $p);
        }

        return $row;
    }
}