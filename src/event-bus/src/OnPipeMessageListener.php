<?php

declare(strict_types=1);

namespace HyperfAdmin\EventBus;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\OnPipeMessage;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;

class OnPipeMessageListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            OnPipeMessage::class,
        ];
    }

    public function process(object $event)
    {
        if($event->data instanceof PipeMessage
           && property_exists($event, 'data')
           && $event->data) {
            $msg = $event->data->data;
            $container = ApplicationContext::getContainer();
            if(is_string($msg['callback'])) {
                if(Str::startsWith($msg['callback'], ['http://', 'https://'])) {
                    $guzzle = $container->get(ClientFactory::class)->create();
                    $response = $guzzle->request('GET', $msg['callback']);
                    $ret = json_decode($response->getBody()->getContents());
                } elseif(preg_match('/([\w+\\\\]+)@(\w+)/', $msg['callback'], $m)) {
                    $ret = call([make($m[1]), $m[2]], [$msg['arg']]);
                } elseif(preg_match('/([\w+\\\\]+)::(\w+)/', $msg['callback'], $m)) {
                    $ret = call([$m[1], $m[2]], [$msg['arg']]);
                }
            } else {
                $ret = call($msg['callback'], (array)$msg['arg']);
            }
            $logger = $container->get(LoggerFactory::class);
            $logger->get('event-bus')->info('event process success', [
                'message' => $msg,
                'result' => $ret,
            ]);
        }
    }
}
