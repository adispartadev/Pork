<?php

/**
 * @license http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public License, Version 3
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */

namespace Pork;

/**
 * Basic process routines.
 *
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */
abstract class Process implements ProcessInterface
{
    /**
     * Normal exit status.
     *
     * @var int
     * @version 0.0.1
     * @since 0.0.1
     */
    const EXIT_NORMAL = 0;

    /**
     * Process PID.
     *
     * @var int
     * @version 0.0.1
     * @since 0.0.1
     */
    protected $pid;

    /**
     * Signals handlers.
     *
     * @var \SplPriorityQueue[]
     * @version 0.0.1
     * @since 0.0.1
     */
    protected $handlers = array();

    /**
     * Initializes a process.
     *
     * By default you create an empty process, but you can pass PID to create instance asociated with it as a controller.
     *
     * @param int $pid Already running process PID.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function __construct($pid = null)
    {
        $this->pid = $pid;
    }

    /**
     * Returns process PID.
     *
     * null PID means, that process is not running.
     *
     * @return int Already running process PID.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Starts process.
     *
     * @return int Created process PID.
     * @throws Exception\PosixException When fork() call fails.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function start()
    {
        // fork here!
        switch ($pid = \pcntl_fork()) {
            case -1:
                throw new Exception\PosixException();

            // child - new process
            case 0:
                // find current PID
                $this->pid = \posix_getpid();

                // install default class signal handlers
                $this->installSignalHandlers();

                // terminate after process ends
                $result = $this->main();
                exit(isset($result) ? $result : self::EXIT_NORMAL);

            // parent
            default:
                // save new process info
                $this->pid = $pid;
                return $pid;
        }
    }

    /**
     * Main process execution method.
     *
     * @return int Exit code.
     * @version 0.0.1
     * @since 0.0.1
     */
    abstract public function main();

    /**
     * Handles signal.
     *
     * @param int $signal Received signal.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function handle($signal)
    {
        // check if any handlers are assigned
        if (isset($this->handlers[$signal])) {
            foreach ($this->handlers[$signal] as $handler) {
                \call_user_func($handler);
            }
        }
    }

    /**
     * Registers signal handler.
     *
     * @param int $signal Received signal.
     * @param callback $callback Callback which will handle given signal.
     * @param int $preiority Priority for given callback.
     * @return Process Self instance.
     * @throws Exception\InvalidArgumentException When argument of invalid type is passed.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function register($signal, $callback, $priority = 0)
    {
        // check arguments types
        if (!\is_int($signal)) {
            throw new Exception\InvalidArgumentException(\sprintf('$signal must be an integer, %s given.', \gettype($signal)));
        }
        if (!\is_callable($callback)) {
            throw new Exception\InvalidArgumentException('$callback is not callable.');
        }
        if (!\is_int($priority)) {
            throw new Exception\InvalidArgumentException(\sprintf('$priority must be an integer, %s given.', \gettype($priority)));
        }

        // initialize signal queue
        if (!isset($this->handlers[$signal])) {
            $this->handlers[$signal] = new \SplPriorityQueue();
            // subscribe for this signal
            \pcntl_signal($signal, array($this, 'handle'));
        }

        // register a callback
        $this->handlers[$signal]->insert($callback, $priority);

        return $this;
    }

    /**
     * Checks whether process is running.
     *
     * @return bool Running status.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function isRunning()
    {
        // sends check signal to process
        return \posix_kill($this->pid, 0);
    }

    /**
     * Sends given signal to process.
     *
     * @return Process Self instance.
     * @throws Exception\InvalidArgumentException When argument of invalid type is passed.
     * @throws Exception\PosixException When sending signal fails.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function signal($signal)
    {
        // check argument type
        if (!\is_int($signal)) {
            throw new Exception\InvalidArgumentException(\sprintf('$signal must be an integer, %s given.', \gettype($signal)));
        }

        // sends signal to process
        if (!\posix_kill($this->pid, $signal)) {
            throw new Exception\PosixException();
        }

        return $this;
    }

    /**
     * Sends process notification to stop.
     *
     * @return Process Self instance.
     * @throws Exception\PosixException When sending signal fails.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function stop()
    {
        return $this->signal(\SIGTERM);
    }

    /**
     * Literaly kills the process.
     *
     * @return Process Self instance.
     * @throws Exception\PosixException When sending signal fails.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function kill()
    {
        return $this->signal(\SIGKILL);
    }

    /**
     * Waits until process ends.
     *
     * @param bool $wait Whether to wait for process, or just check.
     * @return int Process exit code.
     * @throws Exception\PosixException When error occurs.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function wait($wait = true)
    {
        // check for error
        if (\pcntl_waitpid($this->pid, $status, $wait ? 0 : \WNOHANG) === -1) {
            throw new Exception\PosixException();
        }

        // return process status
        return $status;
    }

    /**
     * This method should be overriden in subclasses in order to install default signal handlers.
     *
     * @return Process Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    protected function installSignalHandlers()
    {
        // dummy method - implemented as ampty to not force children classes to add it if not used
        return $this;
    }
}
