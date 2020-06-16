<?php
namespace HyperfAdmin\CronCenter;

use Carbon\Carbon;

abstract class ClassJobAbstract
{
    public $job;

    public $jobManager;

    public $state = [];

    public $logger;

    public function __construct(Crontab $crontab)
    {
        $this->job = $crontab;
        $this->jobManager = make(CronManager::class);
        $this->state = $this->jobManager->getJobState($crontab->getId());
        $this->logger = logger();
    }

    public function run($params = [])
    {
        $func = function () use ($params) {
            try {
                $this->beforeAction($params);
                $this->handle($params);
                $this->afterAction($params);
            } catch (\Throwable $throwable) {
                $this->onError($throwable);
            } finally {
                $this->onComplete();

                return $this->evaluate();
            }
        };

        return $func();
    }

    public function beforeAction($params = [])
    {
        $this->state['start_time'] = ($this->state['start_time'] ?? false) ?: Carbon::now()->toDateTimeString();
        $this->logger->info(sprintf('script job [%s] start at %s', $this->getJobName(), Carbon::now()));
    }

    public function afterAction($params = [])
    {
        $this->logger->info(sprintf('script job [%s] end at %s', $this->getJobName(), Carbon::now()));
    }

    public function onError(\Throwable $throwable)
    {
        $this->logger->error(sprintf('script job [%s] fail: %s', $this->getJobName(), $throwable));
    }

    public function onComplete()
    {
        $this->state['last_time'] = Carbon::now()->toDateTimeString();
        $this->state['counter'] = ($this->state['counter'] ?? 0) + 1;
        $this->state['memory_usage'] = memory_get_usage();
        $this->jobManager->setJobState($this->getJobId(), $this->state);
    }

    public function getJobKey(): string
    {
        return 'cron-center.' . $this->job->getName();
    }

    public function getJobName()
    {
        return $this->job->getName();
    }

    public function getJobId()
    {
        return $this->job->getId();
    }

    public function handle($params)
    {
    }

    protected function evaluate()
    {
    }
}
