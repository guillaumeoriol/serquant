<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Event
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Event;

/**
 * This class contains the entity lifecycle events.
 *
 * @category Serquant
 * @package  Event
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
final class LifecycleEvent
{
    /**
     * This class can not be instantiated.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * The preRemove event occurs for a given entity before the respective
     * gateway delete operation for that entity is executed. If the entity
     * is not managed, an exception is thrown and no event is dispatched.
     * @var string
     */
    const PRE_REMOVE = 'preRemove';

    /**
     * The postRemove event occurs for an entity after the entity has
     * been deleted. It will be invoked after the database delete operations.
     * The postRemove event occurs even if the gateway delete operation returns
     * an unexpected count (different than 1).
     * @var string
     */
    const POST_REMOVE = 'postRemove';

    /**
     * The prePersist event occurs for a given entity before the respective
     * gateway create operation for that entity is executed.
     * @var string
     */
    const PRE_PERSIST = 'prePersist';

    /**
     * The postPersist event occurs for an entity after the entity has been made
     * persistent. It will be invoked after the database insert operations.
     * Generated primary key values are available in the postPersist event.
     * @var string
     */
    const POST_PERSIST = 'postPersist';

    /**
     * The preUpdate event occurs for a given entity before the respective
     * gateway update operation for that entity is executed. If the entity
     * is not managed, an exception is thrown and no event is dispatched.
     * If the entity changeset is empty, no event is dispatched.
     * @var string
     */
    const PRE_UPDATE = 'preUpdate';

    /**
     * The postUpdate event occurs after the database update operations to
     * entity data. The postUpdate event occurs even if the gateway update
     * operation returns an unexpected count (different than 1).
     * @var string
     */
    const POST_UPDATE = 'postUpdate';
}
