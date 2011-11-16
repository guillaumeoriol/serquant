<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Converter\Mapping;

use Doctrine\Common\Cache\Cache;
use Serquant\Converter\Mapping\Loader\LoaderInterface;

/**
 * Factory used to create ClassMetada instances from entity classes and store
 * them in a central place.
 *
 * For the converter to work, we need a method to determine the domain model
 * type of the properties (either to convert from, or to convert to it). This
 * has to be independent from the Data Mapper as not every entity is persistent.
 * At first, I thought I would use the &#64;var annotation. But the metadata
 * system developped by Doctrine can not accomodate such an annotation, as the
 * annotation name has also to be a class name and 'var' is a reserved word in
 * PHP that can't be used as a class name.
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ClassMetadataFactory implements ClassMetadataFactoryInterface
{
    /**
     * Suffix added to the fully qualified class name to construct the cache id.
     * @var string
     */
    const CACHE_SALT = '@[ClassMetadata]';

    /**
     * The loader instance for loading the class metadata
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * The cache instance for caching class metadata
     * @var Cache
     */
    protected $cache;

    /**
     * Class metadata already loaded
     * @var array
     */
    protected $loadedClasses;

    /**
     * Constructs an instance of ClassMetadataFactory
     *
     * @param LoaderInterface $loader The metadata loader
     * @param Cache $cache The optional metadata cache
     */
    public function __construct(LoaderInterface $loader, Cache $cache = null)
    {
        $this->loader = $loader;
        $this->cache = $cache;
        $this->loadedClasses = array();
    }

    /**
     * Gets conversion metadata of a class.
     *
     * @param string $class Fully qualified class name
     * @return ClassMetadata
     */
    public function getClassMetadata($class)
    {
        $class = ltrim($class, '\\');

        if (!isset($this->loadedClasses[$class])) {
            if ($this->cache) {
                $id = $class . self::CACHE_SALT;
                $cached = $this->cache->fetch($id);
                if ($cached !== false) {
                    $this->loadedClasses[$class] = $cached;
                } else {
                    $this->loadMetadata($class);
                    $this->cache->save($id, $this->loadedClasses[$class], null);
                }
            } else {
                $this->loadMetadata($class);
            }
        }

        return $this->loadedClasses[$class];
    }

    /**
     * Loads conversion metadata of a class and stores it internally.
     *
     * @param string $class Fully qualified class name
     * @return void
     */
    protected function loadMetadata($class)
    {
        $metadata = new ClassMetadata($class);

        // Include properties from the parent class
        if ($parent = $metadata->getReflectionClass()->getParentClass()) {
            $metadata->mergeProperties($this->getClassMetadata($parent->getName()));
        }

        $this->loader->loadClassMetadata($metadata);
        $this->loadedClasses[$class] = $metadata;
    }
}