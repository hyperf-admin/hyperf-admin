<?php
namespace HyperfAdmin\RuleEngine\Context;

class TimeContext extends ContextPluginAbstract
{
    protected $persistence = false;

    public function name(): string
    {
        return 'time';
    }

    protected function getTimestamp()
    {
        return time();
    }

    public function getSecond()
    {
        return (int)date('s');
    }

    public function getMinute()
    {
        return (int)date('i');
    }

    public function getHour()
    {
        return (int)date('H');
    }

    public function getDay()
    {
        return (int)date('d');
    }

    public function getMonth()
    {
        return (int)date('m');
    }

    public function getYear()
    {
        return (int)date('Y');
    }

    public function getWeekDay()
    {
        return (int)date('w', time());
    }

    public function getDateTime()
    {
        return date('Y-m-d H:i:s');
    }

    public function getDate()
    {
        return date('Y-m-d');
    }
}
