<?php
namespace HyperfAdmin\DataFocus\Util;

use Throwable;

class SandboxException extends \Exception
{
    public function __construct($message = "", $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
