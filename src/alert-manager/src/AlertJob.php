<?php
declare(strict_types=1);
namespace HyperfAdmin\AlertManager;

use Hyperf\AsyncQueue\Job;
use HyperfAdmin\BaseUtils\Log;
use HyperfAdmin\RuleEngine\BooleanOperation;
use HyperfAdmin\RuleEngine\Context\Context;
use HyperfAdmin\RuleEngine\Context\TimeContext;

class AlertJob extends Job
{
    public $params;

    public function __construct($params)
    {
        // 这里最好是普通数据，不要使用携带 IO 的对象，比如 PDO 对象
        $this->params = $params;
    }

    public function handle()
    {
        $params = $this->params;
        $filter = make(AlertRules::class)->get();
        $logger = Log::get('alert_manager');
        if($filter) {
            $context = (new Context())->register(new TimeContext())
                ->setCustomContext($params['extra'] ?? []);
            $bo = new BooleanOperation($context);
            try {
                $ret = $bo->execute($filter);
            } catch (\Exception $exception) {
                $logger->error('rule compare filed', compact('exception', 'filter'));
            }
        }

        if(!isset($ret) || !$ret) {
            $ret = [
                'alert' => true,
            ];
        }

        $params = array_overlay($ret, $params);
        if(!$ret['alert']) {
            return;
        }

        $robots = make(AlertRobots::class)->get();
        if(!isset($robots[$params['robot_name']])) {
            $logger->warning(sprintf('not support group [%s]', $params['robot_name']));

            return;
        }

        $key = sprintf('alert_manager:frequency:%s:%s', $params['robot_name'], date('YmdHi'));

        $params['webhook'] = $robots[$params['robot_name']]['webhook'];
        $webhook = $params['webhook'] ?? '';
        $type = $params['type'] ?? 'text';
        $message = $params['message'] ?? '';
        $receivers = $params['receivers'] ?? '';
        $method = 'sendText';
        switch($type) {
            case 'markdown':
            case 'md':
                $method = 'sendMarkdown';
                break;
        }

        $send = make(DingTalkRobot::class, ['webhook' => $webhook])->$method($message, $receivers);

        if(!$send) {
            $logger->error('alert_manager send fail', compact('params'));
        }
    }
}

