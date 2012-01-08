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

/**
 * Process that uses custom callback as a single tick function.
 *
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */
class ContinousCallbackProcess extends CallbackProcess implements ContinousProcessInterface
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

        try {
            while (!$this->shutdown) {
                $result = $this->tick();

                // return value means that tick function wants to exit a process
                if (isset($result)) {
                    return $result;
                }
            }
        } catch (\Exception $error) {
            // return exceptions as error codes
            return CallbackProcess::EXIT_CALLBACK_EXCEPTION;
        }

        // normal exit after shutdown
        return \Pork\Process::EXIT_NORMAL;
    }

    /**
     * Main process execution method.
     *
     * @return int Exit code.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function tick()
    {
        return \call_user_func($this->callback);
    }

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
     * Installs shutdown signal handler.
     *
     * @return ContinousCallbackProcess Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    protected function installSignalHandlers()
    {
        // subscribe for SIGTERM signal
        return $this->register(\SIGTERM, array($this, 'handleShutdown'));
    }
}
