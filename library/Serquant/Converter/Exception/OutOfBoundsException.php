<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Exception
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Converter\Exception;

use Serquant\Converter\Exception;

/**
 * Exception thrown if a value is not a valid key. This represents errors that
 * cannot be detected at compile time.
 *
 * @category Serquant
 * @package  Exception
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class OutOfBoundsException
    extends \OutOfBoundsException
    implements Exception
{
}