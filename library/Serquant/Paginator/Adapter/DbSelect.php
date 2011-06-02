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

        $model = $select->getTable();
        return $model->getEntities($data);
    }
}