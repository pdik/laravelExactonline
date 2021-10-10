<?php

namespace Pdik\LaravelExactOnline\Exceptions;

use Exception;
use Throwable;

class CouldNotFindWebhookException extends Exception
{
    /**
     * CouldNotConnectException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, int $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}