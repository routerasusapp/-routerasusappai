<?php

declare(strict_types=1);

namespace Presentation\Exceptions;

use Easy\Http\Message\StatusCode;
use Throwable;

class UnprocessableEntityException extends HttpException
{
    /**
     * @param string $message 
     * @param null|string $param 
     * @param null|Throwable $previous 
     * @return void 
     */
    public function __construct(
        ?string $message = null,
        ?string $param = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, StatusCode::UNPROCESSABLE_ENTITY, $param, $previous);
    }
}