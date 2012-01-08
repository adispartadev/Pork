<?php

/**
 * @license http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public License, Version 3
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */

namespace Pork\Process;

// dependencies
use \Pork\Exception\InvalidArgumentException;
use \Pork\Exception\PosixException;

/**
 * Extended version of {@link \Pork\Process} that provides features usefull for daemon processes.
 *
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */
abstract class Daemon extends \Pork\Process
{
    /**
     * Shutdown flag.
     *
     * @var bool
     * @version 0.0.1
     * @since 0.0.1
     */
    protected $shutdown = false;

    /**
     * Reload flag.
     *
     * @var bool
     * @version 0.0.1
     * @since 0.0.1
     */
    private $reload = false;

    /**
     * Input stream resource.
     *
     * @var resource
     * @version 0.0.1
     * @since 0.0.1
     */
    private $stdin;

    /**
     * Output stream resource.
     *
     * @var resource
     * @version 0.0.1
     * @since 0.0.1
     */
    private $stdout;

    /**
     * Diagnostic stream resource.
     *
     * @var resource
     * @version 0.0.1
     * @since 0.0.1
     */
    private $stderr;

    /**
     * Output logging file.
     *
     * @var string
     * @version 0.0.1
     * @since 0.0.1
     */
    protected $outputLog = '/dev/null';

    /**
     * Error logging file.
     *
     * @var string
     * @version 0.0.1
     * @since 0.0.1
     */
    protected $errorLog = '/dev/null';

    /**
     * User ID for forked process.
     *
     * @var int
     * @version 0.0.1
     * @since 0.0.1
     */
    protected $uid;

    /**
     * Group ID for forked process.
     *
     * @var int
     * @version 0.0.1
     * @since 0.0.1
     */
    protected $gid;

    /**
     * Sets output logging file.
     *
     * @param string $outputLog File path.
     * @return Daemon Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function setOutputLog($outputLog)
    {
        $this->outputLog = $outputLog;

        return $this;
    }

    /**
     * Sets error logging file.
     *
     * @param string $errorLog File path.
     * @return Daemon Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function setErrorLog($errorLog)
    {
        $this->errorLog = $errorLog;

        return $this;
    }

    /**
     * Sets target user ID.
     *
     * @param int $uid User ID.
     * @return Daemon Self instance.
     * @throws InvalidArgumentException When invalid user ID is passed.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function setUid($uid)
    {
        if (!\is_int($uid)) {
            throw new InvalidArgumentException(\sprintf('$uid must be an integer, %s given.', \gettype($uid)));
        } elseif (\posix_getpwuid($uid) === false) {
            throw new InvalidArgumentException(\sprintf('Can not use UID %d.', $uid));
        }

        $this->uid = $uid;

        return $this;
    }

    /**
     * Sets target group ID.
     *
     * @param int $gid Group ID.
     * @return Daemon Self instance.
     * @throws InvalidArgumentException When invalid group ID is passed.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function setGid($gid)
    {
        if (!\is_int($gid)) {
            throw new InvalidArgumentException(\sprintf('$gid must be an integer, %s given.', \gettype($gid)));
        } elseif (\posix_getgrgid($gid) === false) {
            throw new InvalidArgumentException(\sprintf('Can not use GID %d.', $gid));
        }

        $this->gid = $gid;

        return $this;
    }

    /**
     * Daemonizes process.
     *
     * @return Daemon Self instance.
     * @throws PosixException When POSIX operation fails.
     * @version 0.0.1
     * @since 0.0.1
     */
    protected function daemonize()
    {
        // daemonize
        if (\posix_setsid() == -1) {
            throw new PosixException();
        }

        // shadows privileges
        if (isset($this->gid) && !\posix_setgid($this->gid)) {
            throw new PosixException();
        }
        if (isset($this->uid) && !\posix_setgid($this->uid)) {
            throw new PosixException();
        }

        // close default streams
        \fclose(STDIN);
        \fclose(STDOUT);
        \fclose(STDERR);

        // re-open main streams
        // dev null as STDIN
        $this->stdin = \fopen('/dev/null', 'r');
        $this->stdout = \fopen($this->outputLog, 'a');
        $this->stderr = \fopen($this->errorLog, 'a');

        // important note!
        // these streams must be assigned to $this properties!
        // otherwise garbage collecting will prune resources after end of this function code
        // in fact this is only reason to store all $this->std* fields
    }

    /**
     * Run a process in a loop.
     *
     * @return int Exit code.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function main()
    {
        // needed to show off places where signal handlers can interact with process execution
        declare (ticks=1);

        $this->daemonize();

        while (!$this->shutdown) {
            $this->initialize();

            // run process job
            $result = $this->run();

            $this->finalize($result);

            // just a reload - but check if everything was fine
            if ($this->reload && (!isset($result) || $result === 0)) {
                $this->reload = false;
                $this->shutdown = false;
                $this->realod();
            }
        }

        // normal exit after shutdown
        return isset($result) ? $result : \Pork\Process::EXIT_NORMAL;
    }

    /**
     * Runs daemon.
     *
     * @return int Exit code.
     * @version 0.0.1
     * @since 0.0.1
     */
    abstract public function run();

    /**
     * Handles shutdown signal.
     *
     * @version 0.0.1
     * @since 0.0.1
     */
    protected function handleShutdown()
    {
        $this->shutdown = true;
    }

    /**
     * Handles reload signal.
     *
     * @version 0.0.1
     * @since 0.0.1
     */
    protected function handleReload()
    {
        $this->reload = true;
        $this->handleShutdown();
    }

    /**
     * Installs shutdown signal handler.
     *
     * @return Daemon Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    protected function installSignalHandlers()
    {
        // subscribe for SIGTERM signal
        return $this->register(\SIGTERM, array($this, 'handleShutdown'))
        // subscribe for SIGHUP signa;
            ->register(\SIGHUP, array($this, 'handleReload'));
    }

    /**
     * This method should be overriden in subclasses in order to handle resources initialization.
     *
     * @return Daemon Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    protected function initialize()
    {
        // dummy method - implemented as ampty to not force children classes to add it if not used
        return $this;
    }

    /**
     * This method should be overriden in subclasses in order to handle resources cleanup.
     *
     * @param int $result Code with which daemon main routine ended running.
     * @return Daemon Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    protected function finalize($result)
    {
        // dummy method - implemented as ampty to not force children classes to add it if not used
        return $this;
    }

    /**
     * This method should be overriden in subclasses in order to handle configuration reload.
     *
     * @return Daemon Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    protected function reload()
    {
        // dummy method - implemented as ampty to not force children classes to add it if not used
        return $this;
    }
}
