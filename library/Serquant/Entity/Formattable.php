<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Entity
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Entity;

/**
 * This interface is a marker for entities that require a specific formatting
 * process.
 *
 * For instance, when rendering an entity with a {@link
 * http://php.net/manual/en/class.reflectionclass.php ReflectionClass},
 * only annotated properties should be output.<br>
 * See {@link Encoder Serquant\Json\Encoder}.
 *
 * @category Serquant
 * @package  Entity
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
interface Formattable
{
}