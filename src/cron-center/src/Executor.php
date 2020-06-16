<?php
declare(strict_types=1);
namespace HyperfAdmin\CronCenter;

use Carbon\Carbon;
use Closure;
use Hyperf\Contract\ApplicationInterface;
use Hyperf\Crontab\Crontab;
use Hyperf\Crontab\Strategy\Executor as StrategyExecutor;
use Psr\Container\ContainerInterface;
use Swoole\Timer;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class Executor extends StrategyExecutor
{
    private $manager;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->manager = new CronManager();
    }

    public function execute(Crontab $crontab)
    {
        if (!($crontab instanceof Crontab) || !$crontab->getCallback()) {
            return false;
        }
        $handle = $crontab->getType() . 'Handle';
        if (!method_exists($this, $handle)) {
            $this->logger->warning(sprintf('Crontab task [%s] type [%s] not support.', $crontab->getName(), $crontab->getType()));

            return false;
        }
        $diff = $crontab->getExecuteTime()->diffInRealSeconds(new Carbon());
        $callback = $this->$handle($crontab);
        $runner = $callback ? function () use ($callback, $crontab) {
            try {
                $this->startJob($crontab);
                $result = true;
                $runnable = $this->decorateRunnable($crontab, $callback);
                $result = call($runnable);
            } catch (\Throwable $throwable) {
                $this->logger->error(sprintf('Crontab task [%s] error: %s.', $crontab->getName(), $throwable));
                $result = false;
            } finally {
                $this->logResult($crontab, (bool)$result);
            }
        } : null;
        $runner && Timer::after($diff > 0 ? $diff * 1000 : 1, $runner);
    }

    public function callbackHandle(Crontab $crontab)
    {
        [$class, $method] = $crontab->getCallback();
        $parameters = $crontab->getCallback()[2] ?? null;
        if ($class && $method && class_exists($class) && method_exists($class, $method)) {
            return function () use ($class, $method, $parameters, $crontab) {
                $instance = make($class, ['crontab' => $crontab]);
                if ($parameters && is_array($parameters)) {
                    return $instance->{$method}($parameters);
                } else {
                    return $instance->{$method}();
                }
            };
        }

        return null;
    }

    public function commandHandle(Crontab $crontab)
    {
        $input = make(ArrayInput::class, [
            array_merge($crontab->getCallback(), [
                '--job_id' => $crontab->getId(),
                '--job_name' => $crontab->getName(),
            ]),
        ]);
        $output = make(NullOutput::class);
        $application = $this->container->get(ApplicationInterface::class);
        $application->setAutoExit(false);

        return function () use ($application, $input, $output, $crontab) {
            $result = $application->run($input, $output);

            return $result === 0;
        };
    }

    public function evalHandle(Crontab $crontab)
    {
        return function () use ($crontab) {
            return eval($crontab->getCallback());
        };
    }

    protected function runInSingleton(Crontab $crontab, Closure $runnable): Closure
    {
        return function () use ($crontab, $runnable) {
            $taskMutex = $this->getTaskMutex();
            if ($taskMutex->exists($crontab) || !$taskMutex->create($crontab)) {
                $this->logger->info(sprintf('Crontab task [%s] skipped (singleton) execution at %s.', $crontab->getName(), date('Y-m-d H:i:s')));

                return false;
            }
            try {
                $ret = $runnable();
            } finally {
                $taskMutex->remove($crontab);
            }

            return $ret;
        };
    }

    protected function runOnOneServer(Crontab $crontab, Closure $runnable): Closure
    {
        return function () use ($crontab, $runnable) {
            $taskMutex = $this->getServerMutex();
            if (!$taskMutex->attempt($crontab)) {
                $this->logger->info(sprintf('Crontab task [%s] skipped (on server) execution at %s.', $crontab->getName(), date('Y-m-d H:i:s')));

                return false;
            }

            return $runnable();
        };
    }

    public function startJob(Crontab &$crontab)
    {
        $this->logger->info(sprintf('Crontab task [%s] start at %s.', $crontab->getName(), date('Y-m-d H:i:s')));
    }
}
