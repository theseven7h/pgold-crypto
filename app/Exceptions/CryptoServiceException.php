<?php

namespace App\Exceptions;

use Exception;

class CryptoServiceException extends Exception
{
    protected $code = 503;
    protected $message = 'Cryptocurrency service temporarily unavailable';
}
