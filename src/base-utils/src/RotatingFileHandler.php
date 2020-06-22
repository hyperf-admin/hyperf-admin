<?php

namespace HyperfAdmin\BaseUtils;

use Monolog\Handler\RotatingFileHandler as MonologRotatingFileHandler;

class RotatingFileHandler extends MonologRotatingFileHandler
{
    public const FILE_PER_DAY = 'Y-m-d-H';
}
