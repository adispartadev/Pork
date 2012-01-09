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

/**
 * Interface for process data management.
 *
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */
interface StrategyInterface
{
    /**
     * Checks if process is running.
     *
     * @return bool Process running state.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function isRunning();

    /**
     * Returns PID of running process.
     *
     * @return int Running process PID.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function getPid();

    /**
     * Stores PID of started process.
     *
     * @param int $pid Process PID.
     * @return StrategyInterface Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function setPid($pid);

    /**
     * Clears stored data.
     *
     * @return StrategyInterface Self instance.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function clear();
}
