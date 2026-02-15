<?php

namespace App\Exceptions;

use Exception;

class InsufficientFundsException extends Exception
{
    protected $code = 422;
    protected $message = 'Insufficient funds in wallet';
}
