<?php
declare (strict_types=1);
namespace HyperfAdmin\CronCenter\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

/**
 * @property int    $job_id
 * @property string $start_at
 * @property string $end_at
 * @property string $state
 */
class JobRunLog extends BaseModel
{
    protected $table = 'job_run_log';

    protected $connection = 'cron_center';

    protected $fillable = ['job_id', 'start_at', 'end_at', 'state'];

    protected $casts = ['job_id' => 'integer'];
}
