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
 * Process that runs tick function continously.
 *
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */
abstract class ContinousProcess extends \Pork\Process implements ContinousProcessInterface
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

        while (!$this->shutdown) {
            $result = $this->tick();

            // return value means that tick function wants to exit a process
            if (isset($result)) {
                return $result;
            }
        }

        // normal exit after shutdow
        return \Pork\Process::EXIT_NORMAL;
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
