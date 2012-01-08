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
 * General runtime error.
 *
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
 * @version 0.0.1
 * @since 0.0.1
 * @package Pork
 */
class PosixException extends RuntimeException
{
    /**
     * Initializes exception.
     *
     * @param string $message Error message.
     * @param int $code Error code.
     * @param \Exception $previous Previous exception.
     * @version 0.0.1
     * @since 0.0.1
     */
    public function __construct($message = null, $code = null, \Exception $previous = null)
    {
        // detect POSIX error info
        $code = isset($code) ? $code : \posix_get_last_error();
        $message = isset($message) ? $message : \posix_strerr($code);

        parent::__construct($message, $code, $previous);
    }
}
