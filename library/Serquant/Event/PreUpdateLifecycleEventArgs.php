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
 * Class that contains the arguments passed to the listener when a preUpdate
 * lifecycle event is dispatched.
 *
 * Lifecycle events are triggered by the persister during lifecycle transitions
 * of entities.
 *
 * @category Serquant
 * @package  Event
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class PreUpdateLifecycleEventArgs extends LifecycleEventArgs
{
    /**
     * Original state of the entity to be updated
     * @var object
     */
    private $originalState;

    /**
     * Constructs a listener argument container
     *
     * @param object $entity Entity on which the event occurred
     * @param Zend $persister Persister that triggered the event
     * @param object $originalState Original state of the entity to be updated
     */
    public function __construct($entity, $persister, $originalState)
    {
        parent::__construct($entity, $persister);
        $this->originalState = $originalState;
    }

    /**
     * Gets the original state of the entity to be updated
     *
     * @return object
     */
    public function getOriginalState()
    {
        return $this->originalState;
    }
}