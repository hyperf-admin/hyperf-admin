<?php
namespace HyperfAdmin\Admin\Crontab;

use HyperfAdmin\Admin\Model\ExportTasks;
use HyperfAdmin\Admin\Service\ExportService;
use HyperfAdmin\CronCenter\ClassJobAbstract;
use HyperfAdmin\BaseUtils\Log;

class ExportTask extends ClassJobAbstract
{
    public function handle($params = null)
    {
        /**
         * @var ExportService $export
         */
        $export = make(ExportService::class);
        Log::get('export_service')->info(__METHOD__ . ' ==================> started');
        $list = $export->getTasks(0, 0, ['*'], $params ?? []);
        $ids = is_array($list) ? array_column($list, 'id') : $list->pluck('id')->toArray();
        if($ids) {
            ExportTasks::whereIn('id', $ids)
                ->update(['status' => ExportTasks::STATUS_PRE_PROCESSING]); // 设置预处理状态
        }
        foreach($list as $task) {
            $export->processTask($task);
        }
    }

    protected function evaluate(): bool
    {
        return true;
    }
}
