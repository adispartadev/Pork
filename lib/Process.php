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

// dependencies
use Exception\InvalidArgumentException;
use Exception\PosixException;

/**
 * Basic process routines.
 *
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */
abstract class Process extends \Pork\SerializationHandler implements ProcessInterface
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
     * Critical exception that causes process to terminate.
     *
     * @var int
     * @version 0.0.1
     * @since 0.0.1
     */
    const EXIT_UNHANDLED_EXCEPTION = 1;

    /**
     * Default no-op timeout.
     *
     * @var int
     * @version 0.0.1
     * @since 0.0.1
     */
    const NOOP_TIME = 1;

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
     * Assigned controller.
     *
     * @var Control\StrategyInterface
     * @version 0.0.1
     * @since 0.0.1
     */
    protected $control;

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
        if (isset($pid)) {
            $this->setPid($pid);
        }
    }

    /**
     * Returns process PID.
     *
     * null PID means, that process is not running.
     *
     * @return int Current process PID.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Sets process PID.
     *
     * @internal You won't probably need that - if you want persistent control over process use {@link ProcessControl}.
     * @param int $pid Already running process PID.
     * @return Process Self instance.
     * @throws InvalidArgumentException When argument of invalid type is passed.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function setPid($pid)
    {
        // check argument type
        if (!\is_int($pid)) {
            throw new InvalidArgumentException(\sprintf('$pid must be an integer, %s given.', \gettype($pid)));
        }

        $this->pid = $pid;

        return $this;
    }

    /**
     * Sets process control.
     *
     * Note: Don't attach control handler to already running process, it will override it's PID with the one provided by handler!
     *
     * @param Control\StrategyInterface $control Persistent control handler.
     * @return Process Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function setControl(Control\StrategyInterface $control)
    {
        $this->control = $control;

        // automatically bind information from controller
        if ($control->isRunning()) {
            $this->pid = $control->getPid();
        }

        return $this;
    }

    /**
     * Starts process.
     *
     * @return int Created process PID.
     * @throws PosixException When fork() call fails.
     * @throws Control\Exception\AlreadyRunningException When a process control is already taken by another process.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function start()
    {
        // prevent from multiple instances if process control is attached
        if (isset($this->control) && $this->control->isRunning()) {
            throw new Control\Exception\AlreadyRunningException($this->control->getPid());
        }

        // fork here!
        switch ($pid = \pcntl_fork()) {
            case -1:
                throw new PosixException();

            // child - new process
            case 0:
                // find current PID
                $this->pid = \posix_getpid();

                // store PID for control
                if (isset($this->control)) {
                    $this->control->setPid($this->pid);
                }

                try {
                    // install default class signal handlers
                    $this->installSignalHandlers();

                    // terminate after process ends
                    $result = $this->main();
                } catch(\Exception $e) {
                    $result = self::EXIT_UNHANDLED_EXCEPTION;
                }

                // notify controller
                if (isset($this->control)) {
                    $this->control->clear();
                }

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
     * @throws InvalidArgumentException When argument of invalid type is passed.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function register($signal, $callback, $priority = 0)
    {
        // check arguments types
        if (!\is_int($signal)) {
            throw new InvalidArgumentException(\sprintf('$signal must be an integer, %s given.', \gettype($signal)));
        }
        if (!\is_callable($callback)) {
            throw new InvalidArgumentException('$callback is not callable.');
        }
        if (!\is_int($priority)) {
            throw new InvalidArgumentException(\sprintf('$priority must be an integer, %s given.', \gettype($priority)));
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
     * Checks if process with given PID is running.
     *
     * @param int $pid Process PID.
     * @return bool Running status.
     * @version 0.0.1
     * @since 0.0.1
     */
    public static function checkRunning($pid)
    {
        // sends check signal to process
        return \posix_kill($pid, 0);
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
        return isset($this->pid) && self::checkRunning($this->pid);
    }

    /**
     * Sends given signal to process.
     *
     * @return Process Self instance.
     * @throws InvalidArgumentException When argument of invalid type is passed.
     * @throws PosixException When sending signal fails.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function signal($signal)
    {
        // check argument type
        if (!\is_int($signal)) {
            throw new InvalidArgumentException(\sprintf('$signal must be an integer, %s given.', \gettype($signal)));
        }

        // sends signal to process
        if (!\posix_kill($this->pid, $signal)) {
            throw new PosixException();
        }

        return $this;
    }

    /**
     * Sends process notification to stop.
     *
     * Note: This method just sends a signal, does not guarantee, that process will immediatly stop. You have to continously check {@link Process::isRunning()} if process has stopped, or use {@link Process::kill()} to immediatly kill a process.
     *
     * @return Process Self instance.
     * @throws PosixException When sending signal fails.
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
     * Note: This method just immediatly kills a process, without leaving a chance to execute shutdown tasks (like dumping data, closing I/O handles). If you want to cleanly stop a process use {@link Process::stop()}.
     *
     * @return Process Self instance.
     * @throws PosixException When sending signal fails.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function kill()
    {
        return $this->signal(\SIGKILL);
    }

    /**
     * Sends reload request to a thread.
     *
     * @return Process Self instance.
     * @throws PosixException When sending signal fails.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function hup()
    {
        return $this->signal(\SIGHUP);
    }

    /**
     * Hard restart of a process.
     *
     * @param float $timeout Optional timeout which we want to wait until process dies.
     * @return Process Self instance.
     * @throws PosixException When sending signal fails.
     * @throws InvalidArgumentException When argument of invalid type is passed.
     * @throws Exception\TimeoutException When process does not stop within given timeout.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function restart($timeout = null)
    {
        $this->stop();

        // build checking closure
        if (isset($timeout)) {
            // check argument type
            if (!\is_float($timeout) && !\is_int($timeout)) {
                throw new InvalidArgumentException(\sprintf('$timeout must be a float or an integer, %s given.', \gettype($timeout)));
            }

            $end = \microtime(true) + $timeout;

            $check = function() use ($end)
            {
                return \microtime(true) < $end;
            };
        } else {
            // no timeout
            $check = function()
            {
                return true;
            };
        }

        // waits until process stop
        while ($this->isRunning()) {
            // enought of waiting!
            if (!$check()) {
                throw new Exception\TimeoutException($this->pid);
            }

            // wait a moment - it can sometimes take time to end process (I/O, dumping data, etc.)
            $this->noop();
        }

        return $this->start();
    }

    /**
     * Waits until process ends.
     *
     * @param bool $wait Whether to wait for process, or just check.
     * @return int Process exit code.
     * @throws PosixException When error occurs.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function wait($wait = true)
    {
        // check for error
        if (\pcntl_waitpid($this->pid, $status, $wait ? 0 : \WNOHANG) === -1) {
            throw new PosixException();
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

    /**
     * Sleeps to avoid CPU cycles usage.
     *
     * @param int $interval Time interval to wait.
     * @return Process Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    protected function noop($interval = self::NOOP_TIME)
    {
        \sleep($interval);

        return $this;
    }

    /**
     * Clears PID of new instance.
     *
     * @version 0.0.1
     * @since 0.0.1
     */
    public function __clone()
    {
        unset($this->pid);
    }

    /**
     * Directly call process.
     *
     * @return int Created process PID.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function __invoke()
    {
        return $this->start();
    }

    /**
     * Produces plain array container with data.
     *
     * This method is used by ZendFramework during many serialization routines.
     *
     * @return array Plain array with data.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function toArray()
    {
        return array(
            'pid' => $this->pid,
        );
    }

    /**
     * Produces string description of object.
     *
     * This method is used by ZendFramework during various output routines.
     *
     * @return string String description of object.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function toString()
    {
        return \sprintf('[Pork\\Process, %s, %s]', \get_class($this), isset($this->pid) ? \sprintf('PID: %d', $this->pid) : 'stopped');
    }

    /**
     * Recovers instance from plain data.
     *
     * @param array $data Data of object to restore.
     * @param Process $instance Optionaly, target instance which should be recovered.
     * @return Process Recovered instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    public static function fromArray(array $data, SerializationHandler $instance = null)
    {
        if (isset($instance) && !$instance instanceof self) {
            throw new InvalidArgumentException(\sprintf('Pork\\Process::fromArray() can work only on Pork\\Process instances - %s given.', \get_class($instance)));
        }

        $pid = $data['pid'];

        // create new instance
        if (!isset($instance)) {
            $instance = new static($pid);
        } else {
            $instance->pid = $pid;
        }

        return $instance;
    }

    /**
     * Properties overloading (accessing).
     *
     * Magic PHP5 call.
     *
     * @param string $name Property name.
     * @return mixed Property value.
     * @throws InvalidArgumentException When unknown property name is passed.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function __get($name)
    {
        switch ($name) {
            case 'pid':
                return $this->getPid();

            default:
                throw new InvalidArgumentException(\sprintf('Unknown property "%s".', $name));
        }
    }

    /**
     * Properties overloading (mutating).
     *
     * Magic PHP5 call.
     *
     * @param string $name Property name.
     * @param mixed $value Property value.
     * @throws InvalidArgumentException When unknown property name is passed.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'pid':
                $this->setPid($value);
                break;

            default:
                throw new InvalidArgumentException(\sprintf('Unknown property "%s".', $name));
        }
    }

    /**
     * Allows to use any signal as method name.
     *
     * @param string $name Signal name.
     * @param array $arguments List of method arguments (not used by signals thought).
     * @return mixed Method call results.
     * @throws Exception\BadMethodCallException When method that can not be mapped to a signal was called.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function __call($name, array $arguments)
    {
        // create constant name
        $name = \strtoupper($name);

        // check if it's a proper signal name
        if (\substr($name, 0, 3) === 'SIG' && \defined($name)) {
            return $this->signal(\constant($name));
        }

        // if we came here, no signal with such name was available
        throw new Exception\BadMethodCallException(\sprintf('Sorry, I don\'t know how to call "%s".', $name));
    }
}
