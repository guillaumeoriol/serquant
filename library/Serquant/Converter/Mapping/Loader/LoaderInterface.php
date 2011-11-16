<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Loader
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Converter\Mapping\Loader;

use Serquant\Converter\Mapping\ClassMetadata;

/**
 * Requirements a loader class must fulfill.
 *
 * @category Serquant
 * @package  Loader
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
interface LoaderInterface
{
    /**
     * Loads metadata of a class.
     *
     * @param ClassMetadata $metadata The object to put metadata into.
     * @return void
     */
    public function loadClassMetadata(ClassMetadata $metadata);
}