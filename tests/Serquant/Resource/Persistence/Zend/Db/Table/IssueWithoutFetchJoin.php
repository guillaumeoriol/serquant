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

/**
 * Table data gateway for Issue entity performing regular join.
 *
 * As stated by Doctrine documentation:
 *   A join (be it an inner or outer join) becomes a "fetch join" as soon
 *   as fields of the joined entity appear in the SELECT part of the query
 *   outside of an aggregate function. Otherwise its a "regular join".
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class IssueWithoutFetchJoin extends Issue
{
    public function loadEntity($entity, array $row)
    {
        $props = $this->getProperties();
        $p = $this->getDatabasePlatform();

        $props['id']->setValue($entity,
            Type::getType('integer')->convertToPHPValue($row['id'], $p));
        $props['title']->setValue($entity,
            Type::getType('string')->convertToPHPValue($row['title'], $p));

        if ($row['person_id'] !== null) {
            // Partially-loaded association
            $reporterGateway = $this->getPersister()->getTableGateway('Serquant\Resource\Persistence\Zend\Person');
            $reporter = $reporterGateway->newProxyInstance(array('id' => $row['person_id']));
            $props['reporter']->setValue($entity, $reporter);
        }
    }

    public function select($withFromPart = self::SELECT_WITHOUT_FROM_PART)
    {
        $select = $this->_db->select();
        $select->from(array('i' => 'issues'), '*')
               ->joinLeft(
                    array('p' => 'people'),
                    'i.person_id = p.id',
                    array()
               );
        return $select;
    }
}
