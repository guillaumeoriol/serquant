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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\IndexedReader;
use Serquant\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Factory used by the service container (DependencyInjection) to bootstrap
 * the AnnotationReader and return an instance of Reader.
 *
 * The main purpose of this class is to share the AnnotationReader between
 * multiple ClassMetataFactory using annotations to define the metadata. The
 * secondary purpose is to centralize calls to registerAutoloadNamespace;
 * otherwise, if the registration is done through DI factories, some annotations
 * won't be autoloaded properly as the registration may not be complete at the
 * time a file is parsed.
 *
 * @category Serquant
 * @package  DependencyInjection
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class AnnotationReaderFactory
{
    /**
     * Factory method to be called by the service container in order to get
     * a Reader instance.
     *
     * Sample configuration file:
     * <pre>
     * parameters:
     *   annotation_reader_config:
     * # The following one is needed by Doctrine ORM
     *     annotationFile: /path/to/DoctrineAnnotations.php
     * # The following two lines are needed by Symfony Validator
     *     annotationAutoloadNamespaces:
     *       Symfony\Component\Validator\Constraints: /path/to/library
     *       Domain\Entity: /path/to/application
     *     ignoreNotImportedAnnotations: true
     *     cache: apc
     *     cacheDebug: true
     *
     * services:
     *   validator:
     *     class: Doctrine\Common\Annotations\Reader
     *     factory_class: Serquant\DependencyInjection\Factory\AnnotationReaderFactory
     *     factory_method: get
     *     arguments: [%validator_config%]
     * </pre>
     *
     * @param array $config Service configuration options
     * @return \Doctrine\Common\Annotations\Reader
     */
    static public function get(array $config)
    {
        self::setupAnnotationAutoloading($config);

        $reader = new AnnotationReader();
        $reader->setEnableParsePhpImports(true); // Immutable as it is deprecated

        if (isset($config['ignoreNotImportedAnnotations'])) {
            $flag = (bool) $config['ignoreNotImportedAnnotations'];
            $reader->setIgnoreNotImportedAnnotations($flag);
        }

        if (isset($config['namespaceAliases'])) {
            if (!is_array($aliases = $config['namespaceAliases'])) {
                throw new InvalidArgumentException(
                    'The namespaceAlias property must be an array having ' .
                    'namespace for key and alias for value.'
                );
            }
            foreach ($aliases as $namespace => $alias) {
                $reader->setAnnotationNamespaceAlias($namespace, $alias);
            }
        }

        if (isset($config['cache'])) {
            $cache = self::getCache(strtolower($config['cache']));
            $debug = isset($config['cacheDebug'])
                ? (bool) $config['cacheDebug'] : false;
            $reader = new CachedReader(new IndexedReader($reader), $cache, $debug);
        }

        return $reader;
    }

    /**
     * Get a Doctrine cache object from its name.
     *
     * @param string $name Cache name
     * @return \Doctrine\Common\Cache\AbstractCache
     */
    static protected function getCache($name)
    {
        switch($name) {
            case 'apc':
                $cache = new \Doctrine\Common\Cache\ApcCache();
                break;

            case 'memcache':
                $cache = new \Doctrine\Common\Cache\MemcacheCache();
                break;

            case 'xcache':
                $cache = new \Doctrine\Common\Cache\XcacheCache();
                break;

            case 'array':
                $cache = new \Doctrine\Common\Cache\ArrayCache();
                break;

            default:
                throw new InvalidArgumentException(
                    "Invalid annotation cache driver specified: $name"
                );
        }
        return $cache;
    }

    /**
     * Setup the autoloading within the AnnotationRegistry
     *
     * @param array $config Configuration
     * @return void
     * @throws InvalidArgumentException when the annotationAutoloadNamespaces
     * is not of array type.
     */
    static protected function setupAnnotationAutoloading($config)
    {
        // Registration of autoload namespaces
        if (isset($config['annotationAutoloadNamespaces'])) {
            if (!is_array($namespaces = $config['annotationAutoloadNamespaces'])) {
                throw new InvalidArgumentException(
                    'The annotationsAutoloadNamespaces property must be an ' .
                    'array having namespace for key and path for value.'
                );
            }
            AnnotationRegistry::registerAutoloadNamespaces($namespaces);
        }

        // Registration of files containing multiple Annotations classes
        if (isset($config['annotationFile'])) {
            if (!is_array($files = $config['annotationFile'])) {
                $files = array($files);
            }
            foreach ($files as $file) {
                AnnotationRegistry::registerFile($file);
            }
        }

        // Registration of specific autoloaders
        if (isset($config['annotationLoaders'])) {
            if (!is_array($loaders = $config['annotationLoaders'])) {
                $loaders = array($loaders);
            }
            foreach ($loaders as $loader) {
                AnnotationRegistry::registerLoader($loader);
            }
        }
    }
}