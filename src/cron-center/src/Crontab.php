<?php
namespace HyperfAdmin\CronCenter;

use Hyperf\Crontab\Crontab as HyperfCrontab;

class Crontab extends HyperfCrontab
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var bool
     */
    protected $result;

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }
}
