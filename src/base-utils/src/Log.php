<?php
namespace HyperfAdmin\BaseUtils;

use Hyperf\Logger\LoggerFactory;

class Log
{
    public static function get(string $name, $group = 'default')
    {
        return container(LoggerFactory::class)->get($name, $group);
    }
}
