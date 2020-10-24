<?php
namespace HyperfAdmin\CronCenter;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Crontab\Crontab;
use Hyperf\Crontab\Event\CrontabDispatcherStarted;
use Hyperf\Crontab\Parser;
use Hyperf\Crontab\Process\CrontabDispatcherProcess as ProcessCrontabDispatcherProcess;
use Hyperf\Crontab\Scheduler;
use Hyperf\Crontab\Strategy\StrategyInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;

class CrontabDispatcherProcess extends ProcessCrontabDispatcherProcess
{
    public $logger;

    public $scheduler;

    public $strategy;

    public $parser;

    public $counter = 0;

    /**
     * @var \HyperfAdmin\CronCenter\CronManager
     */
    public $cron_manager;

    public $name = 'cron-center-dispatcher';

    public $config;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->logger = $container->get(LoggerFactory::class)->get('cron_center');
        $this->scheduler = $container->get(Scheduler::class);
        $this->strategy = $container->get(StrategyInterface::class);
        $this->parser = make(Parser::class);
        $this->cron_manager = make(CronManager::class);
        $this->config = $container->get(ConfigInterface::class);
    }

    public function isEnable($server): bool
    {
        return $this->config->get('cron_center.enable', false);
    }

    public function handle(): void
    {
        $this->event->dispatch(new CrontabDispatcherStarted());
        while (true) {
            $this->cron_manager->createOrUpdateNode();
            $this->counter++;
            $result = [];
            $crontabs = $this->getCrontabs();
            $this->logger->info(sprintf('Crontab dispatcher the %s time, jobs total %s', $this->counter, count($crontabs)));
            $last = time();
            foreach ($crontabs ?? [] as $key => $crontab) {
                $time = $this->parser->parse($crontab->getRule(), $last);
                if ($time) {
                    foreach ($time as $t) {
                        $result[] = clone $crontab->setExecuteTime($t);
                    }
                }
            }
            $this->sleep();
            foreach ($result as $crontab) {
                $this->strategy->dispatch($crontab);
            }
        }
    }

    private function getCrontabs(): array
    {
        $jobs = $this->cron_manager->getJobs();
        $crontabs = [];
        foreach ($jobs as $crontab) {
            if ($crontab instanceof Crontab) {
                $crontabs[$crontab->getName()] = $crontab;
            }
        }

        return array_values($crontabs);
    }

    private function sleep()
    {
        $current = date('s', time());
        $sleep = 60 - $current;
        $this->logger->info('Crontab dispatcher sleep ' . $sleep . 's.');
        $sleep > 0 && \Swoole\Coroutine::sleep($sleep);
    }
}
