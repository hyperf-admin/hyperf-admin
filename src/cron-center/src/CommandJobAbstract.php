<?php
declare(strict_types=1);
namespace HyperfAdmin\CronCenter;

use Carbon\Carbon;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Coroutine;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CommandJobAbstract extends HyperfCommand
{
    /**
     * @var \HyperfAdmin\CronCenter\CronManager
     */
    public $jobManager;

    /**
     * @var \Hyperf\Contract\StdoutLoggerInterface|mixed
     */
    public $logger;

    /**
     * @var string
     */
    protected $name;

    public $state = [];

    public function configure()
    {
        parent::configure();
        $this->addOption('job_id', '', InputOption::VALUE_REQUIRED, 'job id');
        $this->addOption('job_name', '', InputOption::VALUE_REQUIRED, 'job name');
    }

    public function __construct()
    {
        $container = ApplicationContext::getContainer();
        $this->jobManager = make(CronManager::class);
        parent::__construct();
        $this->logger = $container->get(LoggerFactory::class)->get('cron_center.' . $this->getJobName());
        $this->state = $this->jobManager->getJobState($this->getJobId());
    }

    public function handle()
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $callback = function () {
            try {
                $this->beforeRun();
                call([$this, 'handle']);
                $this->afterRun();
            } catch (\Throwable $throwable) {
                $this->onError($throwable);

                return $throwable->getCode();
            } finally {
                $this->onFinally();
            }

            return 0;
        };
        if ($this->coroutine && !Coroutine::inCoroutine()) {
            run($callback, $this->hookFlags);

            return 0;
        }

        return $callback();
    }

    public function beforeRun()
    {
        $this->state['start_time'] = ($this->state['start_time'] ?? false) ?: Carbon::now()->toDateTimeString();
        $this->logger->info(sprintf('script job [%s] start at %s', $this->getJobName(), Carbon::now()));
    }

    public function afterRun()
    {
        $this->logger->info(sprintf('script job [%s] end at %s', $this->getJobName(), Carbon::now()));
    }

    public function onError(\Throwable $throwable)
    {
        $this->logger->error(sprintf('script job [%s] fail: %s', $this->getJobName(), $throwable));
    }

    public function onFinally()
    {
        $this->state['last_time'] = Carbon::now()->toDateTimeString();
        $this->state['counter'] = ($this->state['counter'] ?? 0) + 1;
        $this->jobManager->setJobState($this->getJobId(), $this->state);
    }

    public function getJobName()
    {
        return $this->input->getOption('job_name');
    }

    public function getJobId()
    {
        return $this->input->getOption('job_id');
    }
}
