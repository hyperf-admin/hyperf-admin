<?php
declare(strict_types=1);
namespace HyperfAdmin\AlertManager;

use Hyperf\AsyncQueue\Process\ConsumerProcess;

class AlertQueueConsumer extends ConsumerProcess
{
    public $queue = 'alert_manager';

    public function isEnable($server): bool
    {
        return config('alert_manager.enable', false);
    }
}
