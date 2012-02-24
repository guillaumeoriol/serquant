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
 * Table data gateway for the Issue entity
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Issue extends Table
{
    /**
     * Table name
     * @var string
     */
    protected $_name = 'issues';

    /**
     * Primary key
     * @var string
     */
    protected $_primary = 'id';

    protected $entityName = 'Serquant\Resource\Persistence\Zend\Issue';

    protected $columnNames = array(
        'id' => 'i.id',
        'title' => 'i.title',
        'reporter' => 'i.person_id',
        'lastname' => 'p.last_name'
    );

    protected $fieldNames = array(
        'id' => 'id',
        'title' => 'title',
        'person_id' => 'reporter',
        'last_name' => 'lastname'
    );

    public function loadEntity($entity, array $row)
    {
        $props = $this->getProperties();
        $p = $this->getDatabasePlatform();

        $props['id']->setValue($entity,
            Type::getType('integer')->convertToPHPValue($row['id'], $p));
        $props['title']->setValue($entity,
            Type::getType('string')->convertToPHPValue($row['title'], $p));

        if ($row['person_id'] !== null) {
            // Fully-loaded association
            $reporterGateway = $this->getPersister()->getTableGateway('Serquant\Resource\Persistence\Zend\Person');
            $reporter = $reporterGateway->newInstance();
            $reporterGateway->loadEntity($reporter, array(
            	'id' => $row['person_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name']
            ));
            $props['reporter']->setValue($entity, $reporter);
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
        $select->from(array('i' => 'issues'), '*')
               ->joinLeft(
                    array('p' => 'people'),
                    'i.person_id = p.id',
                    '*'
               );
        return $select;
    }

    public function selectPairs($id, $label)
    {
        $select = $this->_db->select();
        $select->from(array('i' => 'issues'), array($id, $label))
               ->joinLeft(
                    array('p' => 'people'),
                    'i.person_id = p.id',
                    array()
               );
        return $select;

    }
}
