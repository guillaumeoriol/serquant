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

use Serquant\Persistence\Zend\Configuration;
use Serquant\Persistence\Zend\Persister;

/**
 * Factory used by the service container (DependencyInjection) to bootstrap
 * the Zend persister and return an instance of it.
 *
 * @category Serquant
 * @package  DependencyInjection
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ZendPersisterFactory
{
    /**
     * Zend persister configuration options
     * @var Configuration
     */
    private $config = null;

    /**
     * Constructs the factory instance
     *
     * @param array $options Configuration options
     */
    private function __construct(array $options)
    {
        $this->config = new Configuration();

        if (isset($options['gatewayMap'])) {
            $this->config->setGatewayMap($options['gatewayMap']);
        }

        if (isset($options['eventManager'])) {
            $this->config->setEventManager($options['eventManager']);
        }

        if (isset($options['proxyNamespace'])) {
            $this->config->setProxyNamespace($options['proxyNamespace']);
        }
    }

    /**
     * Gets the Zend persister instance
     *
     * @return Persister
     */
    public function getPersister()
    {
        return new Persister($this->config);
    }

    /**
     * Factory method to be called by the service container in order to get
     * a Zend persister instance.
     *
     * Sample configuration file:
     * <pre>
     * parameters:
     *   persister_config:
     *     gatewayMap:
     *       My\Domain\Entity\User: My\Domain\Gateway\User
     *       # ...
     *     eventManager: @event_manager
     *     proxyNamespace: My\Domain\Proxy
     *
     * services:
     *   event_manager:
     *     # ...
     *   persister:
     *     class: Serquant\Persistence\Zend\Persister
     *     factory_class: Serquant\DependencyInjection\Factory\ZendPersisterFactory
     *     factory_method: get
     *     arguments: [%persister_config%]
     * </pre>
     *
     * @param array $options Configuration options
     * @return Persister
     */
    public static function get(array $options = array())
    {
        $factory = new ZendPersisterFactory($options);
        return $factory->getPersister();
    }
}
