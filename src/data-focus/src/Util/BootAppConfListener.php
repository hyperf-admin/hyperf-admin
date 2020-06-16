<?php
namespace HyperfAdmin\DataFocus\Util;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use HyperfAdmin\DataFocus\Service\Dsn;

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
        make(Dsn::class)->initAll();
    }
}
