<?php
namespace HyperfAdmin\CronCenter;

use Hyperf\Process\AbstractProcess;
use Psr\Container\ContainerInterface;

class AlertManagerProcess extends AbstractProcess
{
    protected $jobManager;

    public function __construct(ContainerInterface $container)
    {
        $this->jobManager = make(CronManager::class);
        parent::__construct($container);
    }

    public function handle(): void
    {
        // todo alert
        while (true) {
            $jobs = $this->jobManager->getJobs();
            \Swoole\Coroutine::sleep(1);
        }
    }
}
