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
 * Interface for process that executes some task without infinite loop.
 *
 * This interface should be implemented by processes that don't want to worry about process running handling and just want to define some task that should be done continously.
 *
 * You probably don't need to use this interface directly - you will wather want to use either {@link ContinousProcess} or {@link ContinousCallbackProcess} classes that provide full logic for running your "ticks" in loop.
 *
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */
interface ContinousProcessInterface
{
    /**
     * Single tick method that will be running in a loop.
     *
     * Note: Process will run, until this method returns exit code, while 0 is a valid exit code. Thus `return 0` will end process (with 0 exit code), while `return` will just move to next loop iteration.
     *
     * @return int Exit code. Process will run, until tick function returns a value.
     * @version 0.0.1
     * @since 0.0.1
     */
    protected function tick();
}
