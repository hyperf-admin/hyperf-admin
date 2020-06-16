<?php
namespace HyperfAdmin\BaseUtils;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class HAStreamHandler extends StreamHandler
{
    /**
     * 修复handler写日志判断级别问题bug
     */
    public function isHandling(array $record): bool
    {
        $level_code = Logger::toMonologLevel($record['level']);

        return $level_code >= $this->level;
    }
}
