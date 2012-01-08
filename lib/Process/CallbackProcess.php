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
 * Process that runs custom callback.
 *
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */
class CallbackProcess extends \Pork\Process
{
    /**
     * Callback thrown an exception.
     *
     * @var int
     * @version 0.0.1
     * @since 0.0.1
     */
    const EXIT_CALLBACK_EXCEPTION = 1;

    /**
     * Callback to call.
     *
     * @var callback
     * @version 0.0.1
     * @since 0.0.1
     */
    protected $callback;

    /**
     * Sets callback to run.
     *
     * @param callback $callback Callback to be run as process.
     * @throws \Pork\Exception\InvalidArgumentException When argument of invalid type is passed.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function setCallback($callback)
    {
        // check argument type
        if (!\is_callable($callback)) {
            throw new \Pork\Exception\InvalidArgumentException('$callback is not callable.');
        }

        $this->callback = $callback;

        return $this;
    }

    /**
     * Main process execution method.
     *
     * @return int Exit code.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function main()
    {
        try {
            return \call_user_func($this->callback);
        } catch (\Exception $error) {
            // return exceptions as error codes
            return self::EXIT_CALLBACK_EXCEPTION;
        }
    }
}
