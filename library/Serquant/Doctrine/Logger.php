<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Doctrine
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Doctrine;

use Doctrine\DBAL\Logging\SQLLogger;

/**
 * Doctrine SQL logger implementation to be used in Zend Framework projects.
 *
 * @category Serquant
 * @package  Doctrine
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Logger implements SQLLogger
{
    /**
     * Logger
     * @var \Zend_Log
     */
    protected $logger;

    /**
     * Point in time at which the query starts
     * @var float
     */
    protected $start;

    /**
     * Constructor
     *
     * @param mixed $options Zend_Log#factory options
     */
    public function __construct($options)
    {
        $this->logger = \Zend_Log::factory($options);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $sql The SQL to be executed.
     * @param array $params The SQL parameters.
     * @param array $types The SQL parameter types.
     * @return void
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        if ($params !== null) {
            $sql = $this->format($sql, $params);
        }
        $this->start = microtime(true);
        $this->logger->log($sql, \Zend_Log::INFO);
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function stopQuery()
    {
        $duration = (microtime(true) - $this->start) * 1000;
        $this->logger->log("Elapsed time: $duration ms", \Zend_Log::INFO);
    }

    /**
     * Formats the given SQL statement with the specified parameters
     *
     * @param string $sql The SQL query
     * @param array $params The SQL query parameters
     * @return string
     */
    protected function format($sql, array $params)
    {
        $format = str_replace('?', '%s', $sql);
        $replacements = array();
        foreach ($params as $param) {
            $replacements[] = is_object($param) ? print_r($param, true) : $param;
        }
        return vsprintf($format, $replacements);
    }
}