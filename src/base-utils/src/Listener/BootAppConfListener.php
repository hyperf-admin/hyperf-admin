<?php
namespace HyperfAdmin\BaseUtils\Listener;

use Hyperf\Database\Model\Builder;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use HyperfAdmin\BaseUtils\Log;

class BootAppConfListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event)
    {
        Builder::macro('getAsArray', function () {
            /** @var \Hyperf\Database\Query\Builder $this */
            $ret = $this->get();

            return $ret ? $ret->toArray() : [];
        });
        Builder::macro('firstAsArray', function () {
            /** @var \Hyperf\Database\Query\Builder $this */
            $ret = $this->first();

            return $ret ? $ret->toArray() : [];
        });
        set_error_handler(function ($level, $message, $file = '', $line = 0, $context = []) {
            if(error_reporting() & $level) {
                $exception = new \ErrorException($message, 0, $level, $file, $line);
                Log::get('php_output_error')->error((string)$exception);
            }
        });
    }
}
