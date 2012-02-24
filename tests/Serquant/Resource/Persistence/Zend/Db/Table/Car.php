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
 * Table data gateway for the Car entity
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Car extends Table
{
    /**
     * Table name
     * @var string
     */
    protected $_name = 'cars';

    /**
     * Primary key
     * @var string
     */
    protected $_primary = 'id';

    protected $entityName = 'Serquant\Resource\Persistence\Zend\Car';

    protected $fieldNames = array(
        'id' => 'id',
        'person_id' => 'owner'
    );

    public function loadEntity($entity, array $row)
    {
        $props = $this->getProperties();
        $p = $this->getDatabasePlatform();

        $props['id']->setValue($entity,
            Type::getType('integer')->convertToPHPValue($row['id'], $p));

        if ($row['person_id'] !== null) {
            // Fully-loaded association
            $ownerGateway = $this->getPersister()->getTableGateway('Serquant\Resource\Persistence\Zend\Person');
            $owner = $ownerGateway->newInstance();
            $ownerGateway->loadEntity($owner, array(
            	'id' => $row['person_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name']
            ));
            $props['owner']->setValue($entity, $owner);
        }
    }

    /**
     * Returns an instance of a Zend_Db_Table_Select object.
     *
     * @param bool $withFromPart Whether or not to include the from part of the select based on the table
     * @return Zend_Db_Table_Select
     */
    public function select($withFromPart = self::SELECT_WITHOUT_FROM_PART)
    {
        $select = $this->_db->select();
        $select->from(array('c' => 'cars'), '*')
               ->joinLeft(
                    array('p' => 'people'),
                    'c.person_id = p.id',
                    '*'
               );
        return $select;
    }
}