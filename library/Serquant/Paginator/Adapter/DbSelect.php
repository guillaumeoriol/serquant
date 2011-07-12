<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Paginator
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Paginator\Adapter;

use Serquant\Persistence\Persistence;

/**
 * Paginator adapter used to get entities instead of an associative array of
 * column names/values.
 *
 * @category Serquant
 * @package  Paginator
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class DbSelect extends \Zend_Paginator_Adapter_DbSelect
{
    /**
     * Persistence layer
     * @var Persistence
     */
    private $persister;

    /**
     * Class name of the entities
     * @var string
     */
    private $entityName;

    /**
     * Constructor.
     *
     * @param \Zend_Db_Select $select The select query
     * @param Persistence $persister Persister
     * @param string $entityName Class name of the entities to be paginated
     */
    public function __construct(
        \Zend_Db_Select $select,
        Persistence $persister,
        $entityName
    ) {
        $this->_select = $select;
        $this->persister = $persister;
        $this->entityName = $entityName;
    }

    /**
     * Returns an array of entities for a page.
     *
     * @param integer $offset Page offset
     * @param integer $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $select = $this->_select;
        $select->limit($itemCountPerPage, $offset);
        $data = $select->query()->fetchAll(\Zend_Db::FETCH_ASSOC);

        return $this->persister->loadEntities($this->entityName, $data);
    }
}