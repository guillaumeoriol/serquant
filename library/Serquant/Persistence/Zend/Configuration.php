<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Persistence
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Persistence\Zend;

use Doctrine\Common\EventManager;
use Serquant\Persistence\Exception\InvalidArgumentException;

/**
 * Configuration container for the Zend persister.
 *
 * @category Serquant
 * @package  Persistence
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 * @internal When adding a new configuration option just write a getter/setter
 * pair and add the option to the attributes array to set a default value.
 */
class Configuration
{
    /**
     * The attributes that are contained in the configuration.
     * Values are default values.
     * @var array
     */
    protected $attributes = array(
        'gatewayMap' => array(),
        'proxyNamespace' => ''
    );

    /**
     * Sets the gateway map
     *
     * @param array $gatewayMap Gateway map
     * @return void
     */
    public function setGatewayMap(array $gatewayMap = array())
    {
        $this->attributes['gatewayMap'] = $gatewayMap;
    }

    /**
     * Gets the gateway map
     *
     * @return array
     */
    public function getGatewayMap()
    {
        return isset($this->attributes['gatewayMap']) ?
            $this->attributes['gatewayMap'] : null;
    }

    /**
     * Sets the event manager
     *
     * @param array $eventManager Event manager
     * @return void
     */
    public function setEventManager(EventManager $eventManager)
    {
        $this->attributes['eventManager'] = $eventManager;
    }

    /**
     * Gets the event manager
     *
     * @return EventManager
     */
    public function getEventManager()
    {
        return isset($this->attributes['eventManager']) ?
            $this->attributes['eventManager'] : null;
    }

    /**
     * Sets the proxy namespace
     *
     * @param string $proxyNamespace Proxy namespace
     * @return void
     */
    public function setProxyNamespace($proxyNamespace)
    {
        if (preg_match('/^[A-Z][a-zA-Z0-9_\\\\]*[a-zA-Z0-9_]$/', $proxyNamespace)) {
            $this->attributes['proxyNamespace'] = $proxyNamespace;
        } else {
            throw new InvalidArgumentException(
                'The proxy namespace shall not have a leading or trailing ' .
                'backslash.', 10
            );
        }
    }

    /**
     * Gets the proxy namespace
     *
     * @return string
     */
    public function getProxyNamespace()
    {
        return isset($this->attributes['proxyNamespace']) ?
            $this->attributes['proxyNamespace'] : null;
    }
}