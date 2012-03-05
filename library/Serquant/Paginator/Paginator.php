<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Paginator
 * @author   Baptiste Tripot <bt@technema.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Paginator;

use Serquant\Persistence\Persistence;
use Serquant\Paginator\Exception\RuntimeException;

/**
 * Serquant Paginator
 *
 * @category Serquant
 * @package  Paginator
 * @author   Baptiste Tripot <bt@technema.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Paginator extends \Zend_Paginator
{
    /**
     * Offset of the first item to retrieve
     * @var integer
     */
    private $itemOffset;

    /**
     * Sets the offset of the first item to retrieve
     *
     * @param integer $offset Item offset
     * @return void
     */
    public function setItemOffset($offset)
    {
        $this->itemOffset = $offset;
    }

    /**
     * Gets the offset of the first item to retrieve
     *
     * @return integer
     */
    public function getItemOffset()
    {
        return $this->itemOffset;
    }

    /**
     * Gets the items for the current range
     *
     * @return Traversable
     */
    public function getCurrentItems()
    {
        if ($this->_currentItems === null) {
            $items = $this->_adapter->getItems(
                $this->itemOffset,
                $this->getItemCountPerPage()
            );

            if (!$items instanceof \Traversable) {
                $items = new \ArrayIterator($items);
            }

            $this->_currentItems = $items;
        }

        return $this->_currentItems;
    }

    /**
     * Forbids usage of the cache
     *
     * @param Zend_Cache_Core $cache the cache the user is trying to use
     * @return void
     * @throws RuntimeException
     */
    public static function setCache(\Zend_Cache_Core $cache)
    {
        throw new RuntimeException('Usage of Cache is disabled for this Paginator');
    }

    /**
     * Forbids usage of the filter
     *
     * @param Zend_Filter_Interface $filter the filter the user is trying to use
     * @return void
     * @throws RuntimeException
     */
    public function setFilter(\Zend_Filter_Interface $filter = null)
    {
        throw new RuntimeException('Usage of Filter is disabled for this Paginator');
    }
}