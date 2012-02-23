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

use Doctrine\Common\EventArgs;
use Serquant\Persistence\Zend\Persister;

/**
 * Class that contains the arguments passed to the listener when a lifecycle
 * event is dispatched.
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
class LifecycleEventArgs extends EventArgs
{
    /**
     * Persister that triggered the event
     * @var Persister
     */
    private $persister;

    /**
     * Entity on which the event occurred
     * @var object
     */
    private $entity;

    /**
     * Constructs a listener argument container
     *
     * @param object $entity Entity on which the event occurred
     * @param Persister $persister Persister that triggered the event
     */
    public function __construct($entity, Persister $persister)
    {
        $this->entity = $entity;
        $this->persister = $persister;
    }

    /**
     * Gets the entity on which the event occurred
     *
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Gets the persister that triggered the event
     *
     * @return Persister
     */
    public function getPersister()
    {
        return $this->persister;
    }
}