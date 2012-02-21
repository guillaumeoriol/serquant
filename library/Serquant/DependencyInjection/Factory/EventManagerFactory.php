<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  DependencyInjection
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\DependencyInjection\Factory;

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Serquant\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Factory used by the service container (DependencyInjection) to bootstrap
 * the EventManager and return an instance of it.
 *
 * @category Serquant
 * @package  DependencyInjection
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class EventManagerFactory
{
    /**
     * Doctrine Common event manager
     * @var EventManager
     */
    private $eventManager = null;

    /**
     * Constructs the event manager instance
     *
     * @param array $options Factory configuration options
     * @return void
     */
    private function __construct(array $options)
    {
        $this->eventManager = new EventManager();

        if (isset($options['listeners'])) {
            $this->addListeners($options['listeners']);
        }

        if (isset($options['subscribers'])) {
            $this->addSubscribers($options['subscribers']);
        }
    }

    /**
     * Gets the event manager instance
     *
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * Factory method to be called by the service container in order to get
     * an event manager instance.
     *
     * Sample configuration file:
     * <pre>
     * parameters:
     *   event_manager_config:
     *     listeners:
     *       -
     *         events: postDelete
     *         listener: My\Listener\CascadeDelete
     *       -
     *         events:
     *           - postCreate
     *           - postUpdate
     *         listener: My\Listener\Informer
     *     subscribers:
     *       - My\Subscriber\Logger
     * services:
     *   event_manager:
     *     class: Doctrine\Common\EventManager
     *     factory_class: Serquant\DependencyInjection\Factory\EventManagerFactory
     *     factory_method: get
     *     arguments: [%event_manager_config%]
     * </pre>
     *
     * @param array $config Factory configuration options
     * @return EventManager
     */
    public static function get(array $config = array())
    {
        $factory = new EventManagerFactory($config);
        return $factory->getEventManager();
    }

    /**
     * Adds listeners to the event manager
     *
     * @param array $listeners Listeners
     * @return void
     * @throws InvalidArgumentException if one of the given listeners is not
     * a string nor an object or if it is an object of the wrong class
     */
    protected function addListeners(array $listeners)
    {
        foreach ($listeners as $options) {
            if (!isset($options['events']) || !isset($options['listener'])) {
                throw new InvalidArgumentException(
                    "Either 'event' or 'listener' is not set in the listener " .
                    'options. Listener not added to the event manager.', 20
                );
            }

            $listener = $options['listener'];
            if (is_string($listener)) {
                $listener = new $listener;
            }

            if (!is_object($listener)) {
                throw new InvalidArgumentException(
                    'The given listener ('. $listener .
                    ') is not a string nor an object', 21
                );
            }

            $this->eventManager->addEventListener($options['events'], $listener);
        }
    }

    /**
     * Adds subscribers to the event manager
     *
     * @param array $subscribers Subscribers
     * @return void
     * @throws InvalidArgumentException if one of the given subscribers is not
     * a string nor an object or if it is an object of the wrong class
     */
    protected function addSubscribers(array $subscribers)
    {
        foreach ($subscribers as $subscriber) {
            if (is_string($subscriber)) {
                $subscriber = new $subscriber;
            }

            if (!is_object($subscriber)) {
                throw new InvalidArgumentException(
                    'The given subscriber ('. $subscriber .
                    ') is not a string nor an object', 10
                );
            }

            if (!$subscriber instanceof EventSubscriber) {
                throw new InvalidArgumentException(
                    'The given subscriber (' . get_class($subscriber) .
                    ') does not implement Doctrine\Common\EventSubscriber', 11
                );
            }

            $this->eventManager->addEventSubscriber($subscriber);
        }
    }
}