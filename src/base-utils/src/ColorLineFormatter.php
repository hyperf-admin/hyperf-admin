<?php
namespace HyperfAdmin\BaseUtils;

use Monolog\Formatter\LineFormatter;

class ColorLineFormatter extends LineFormatter
{
    public $level_color_map = [
        'ERROR' => "\e[0;31m{log_str}\e[0m",
        'INFO' => "\e[0;32m{log_str}\e[0m",
        'WARNING' => "\e[0;33m{log_str}\e[0m",
    ];

    public function format(array $record): string
    {
        $log = parent::format($record);

        return $this->logColor($record['level_name'], $log);
    }

    public function logColor($level_name, $log)
    {
        $color_format = $this->level_color_map[$level_name] ?? '{log_str}';

        return str_replace('{log_str}', $log, $color_format);
    }
}
