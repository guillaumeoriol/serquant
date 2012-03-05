<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Exception
 * @author   Baptiste Tripot <bt@technema.fr>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Paginator\Exception;

use Serquant\Paginator\Exception;

/**
 * Exception thrown if a disabled function is called
 *
 * @category Serquant
 * @package  Paginator
 * @author   Baptiste Tripot <bt@technema.fr>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class RuntimeException
    extends \RuntimeException
    implements Exception
{
}