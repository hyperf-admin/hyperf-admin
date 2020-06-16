<?php
namespace HyperfAdmin\AlertManager;

use mysql_xdevapi\Exception;
use HyperfAdmin\BaseUtils\Guzzle;
use HyperfAdmin\BaseUtils\Log;

class DingTalkRobot implements SenderInterface
{
    protected $webhook;

    public function __construct(string $webhook)
    {
        if(!$webhook) {
            throw new Exception('webhook is invalide');
        }
        $this->webhook = $webhook;
    }

    public function sendText($message, $at = 'all')
    {
        $params = [
            'msgtype' => 'text',
            'text' => [
                'content' => $message,
            ],
        ];

        return $this->send($params, $at);
    }

    public function sendMarkdown($message, $at = 'all')
    {
        $params = [
            'msgtype' => 'markdown',
            'markdown' => [
                'text' => $message,
                'title' => '告警信息',
            ],
        ];

        return $this->send($params, $at);
    }

    public function send($params, $at = 'all')
    {
        if($at == 'all') {
            $params['at'] = ['isAtAll' => true];
        }
        if(is_array($at)) {
            $params['at'] = ['atMobiles' => $at];
        }
        $ret = Guzzle::post($this->webhook, $params);
        if($ret['errcode'] ?? -1 !== 0) {
            Log::get('alert_manager')
                ->error(sprintf('alert_manager send message filed'), compact('params', 'ret'));

            return false;
        }

        return true;
    }
}
