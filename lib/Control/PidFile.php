<?php

/**
 * @license http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public License, Version 3
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */

namespace Pork\Control;

// dependencies
use Exception\InvalidArgumentException;

/**
 * PID-file based strategy.
 *
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */
class PidFile implements StrategyInterface
{
    /**
     * PID storage file.
     *
     * @var string
     * @version 0.0.1
     * @since 0.0.1
     */
    protected $pidFile;

    /**
     * PID already read from file, to avoid multiple I/O calls.
     *
     * @var int
     * @version 0.0.1
     * @since 0.0.1
     */
    protected $pid;

    /**
     * Initializes storage on given file.
     *
     * @param string $pidFile PID file.
     * @throws InvalidArgumentException If $pidfile is not a valid file storage path.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function __construct($pidFile)
    {
        // just a lot of cases
        if (\file_exists($pidFile)) {
            if (!\is_file($pidFile)) {
                throw new InvalidArgumentException(\sprintf('"%s" is not a regular file, which could store a PID.', $pidFile));
            }
            if (!\is_readable($pidFile)) {
                throw new InvalidArgumentException(\sprintf('Can not read from "%s".', $pidFile));
            }
            if (!\is_writeable($pidFile)) {
                throw new InvalidArgumentException(\sprintf('Can not write to "%s".', $pidFile));
            }
        } else {
            $path = \dirname($pidFile);

            if (!\is_dir($path)) {
                throw new InvalidArgumentException(\sprintf('"%s" does not exist or is not a directory, where a pidfile could be created.', $path));
            }
            if (!\is_writeable($path)) {
                throw new InvalidArgumentException(\sprintf('Can not create pidfile in "%s".', $path));
            }
        }

        $this->pidFile = $pidFile;
    }

    /**
     * Checks if process is running.
     *
     * @return bool Process running state.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function isRunning()
    {
        // no PID - nothing more to do
        if (!\file_exists($this->pidFile)) {
            return false;
        }

        $pid = \file_get_contents($this->pidFile);

        if (\is_numeric($pid)) {
            if (\Pork\Process::checkRunning($pid)) {
                // remember it here, why bother in future?
                $this->pid = $pid;
                // unfortunately we can't check if it's our process
                return true;
            }
        }

        // not running - delete orphaned PID file
        $this->clear();
        return false;
    }

    /**
     * Returns PID of running process.
     *
     * @return int Running process PID.
     * @throws Exception\NotRunningException If there is no running process.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function getPid()
    {
        // acquire PID from PID file
        if (!isset($this->pid)) {
            // check if daemon is running
            if (!$this->isRunning()) {
                throw new Exception\NotRunningException();
            }

            // even though this is not clear from the logic, isRunning() already remembers the PID, so this line is not needed
            //$this->pid = \file_get_contents($this->pidFile);
        }

        return $this->pid;
    }

    /**
     * Stores PID of started process.
     *
     * @param int $pid Process PID.
     * @return StrategyInterface Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
        \file_put_contents($this->pidFile, $pid);

        return $this;
    }

    /**
     * Clears stored data.
     *
     * @return StrategyInterface Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function clear()
    {
        \unlink($this->pidFile);
        unset($this->pid);

        return $this;
    }
}
