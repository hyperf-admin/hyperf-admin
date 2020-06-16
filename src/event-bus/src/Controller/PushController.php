<?php
namespace HyperfAdmin\EventBus\Controller;

use HyperfAdmin\BaseUtils\Constants\ErrorCode;
use HyperfAdmin\BaseUtils\Controller\AbstractController;

class PushController extends AbstractController
{
    public function push()
    {
        $type = $this->request->input('type');
        if(!$type) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM, 'place set message type');
        }
        $message = $this->request->input('message');
        if(!$message || !is_array($message)) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM, 'message is invalide');
        }
        $instance = $this->request->input('instance');
        if(!$instance) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM, 'place chose instance');
        }
        switch($type) {
            case 'amqp':
                $exchange = $this->request->input('exchange');
                $routingKey = $this->request->input('routingKey');
                $ret = amqp_push($exchange, $routingKey, $message, [], $instance);
                break;
            case 'kafka':
                $topic = $this->request->input('topic');
                $ret = kafka_push($topic, $message, $instance);
                break;
            default:
                return $this->fail(ErrorCode::CODE_ERR_SYSTEM, 'not support queue type');
                break;
        }

        return $ret ? $this->success() : $this->fail(ErrorCode::CODE_ERR_SYSTEM, 'push message faile');
    }
}
