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

/**
 * Requirements a ClassMetadataFactory must fulfill.
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
interface ClassMetadataFactoryInterface
{
    /**
     * Gets metatadata of a class.
     *
     * @param string $class Fully qualified class name
     * @return ClassMetadata
     */
    public function getClassMetadata($class);
}