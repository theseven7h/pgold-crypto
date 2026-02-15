<?php

namespace App\Exceptions;

use Exception;

class InvalidTradeException extends Exception
{
    protected $code = 422;
    protected $message = 'Invalid trade parameters';
}
