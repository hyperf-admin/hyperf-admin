<?php
declare(strict_types=1);
namespace HyperfAdmin\BaseUtils\Listener;

use Hyperf\Database\Events\StatementPrepared;
use Hyperf\Event\Contract\ListenerInterface;
use PDO;

class FetchModeListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            StatementPrepared::class,
        ];
    }

    public function process(object $event)
    {
        if($event instanceof StatementPrepared) {
            $event->statement->setFetchMode(PDO::FETCH_ASSOC);
        }
    }
}
