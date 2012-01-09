<?php

/**
 * @license http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public License, Version 3
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */

namespace Pork\Exception;

/**
 * Process communication timeout.
 *
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */
class TimeoutException extends RuntimeException
{
    /**
     * Process PID.
     *
     * @var int
     * @version 0.0.1
     * @since 0.0.1
     */
    protected $pid;

    /**
     * Initializes exception.
     *
     * @param int $pid Process PID.
     * @param \Exception $previous Previous exception.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function __construct($pid, \Exception $previous = null)
    {
        // sets up info
        $this->pid = $pid;

        parent::__construct(\sprintf('Timeout while waiting for process with PID %d to react.', $pid), 0, $previous);
    }

    /**
     * Returns PID of process that did not respond.
     *
     * @return int Process PID.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function getPid()
    {
        return $this->pid;
    }
}
