<?php

namespace HyperfAdmin\ConfigCenter;

use Hyperf\Command\Event\BeforeHandle;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeWorkerStart;
use Hyperf\Process\Event\BeforeProcessHandle;
use HyperfAdmin\ConfigCenter\Service\ConfigCenterService;
use Swoole\Timer;

class BootProcessListener implements ListenerInterface
{
    public $version;

    public $service;

    public $log;

    public $config;

    public function __construct()
    {
        $this->log = logger()->get('config_center.tick');
        $this->service = make(ConfigCenterService::class);
        $this->config = container(ConfigInterface::class);
    }

    public function listen(): array
    {
        return [
            BeforeWorkerStart::class,
            BeforeProcessHandle::class,
            BeforeHandle::class,
        ];
    }

    public function process(object $event)
    {
        if (!config('config_center.enable', false)) {
            return;
        }

        $this->log->info('tick begin');
        $interval = config('config_center.interval', 1);
        $this->load();
        Timer::tick($interval * 1000, function () {
            $this->load();
        });
    }

    public function load()
    {
        try {
            $new_version = $this->service->version();
            if ($this->version == $new_version) {
                return;
            }
            $conf_arr = $this->service->get();
            foreach ($conf_arr as $key => $conf) {
                $current_conf = $this->config->get($key, []);
                $this->config->set($key, array_overlay($conf, $current_conf));
            }
            $this->version = $new_version;
            $this->log->info('load config success!');
        } catch (\Exception $e) {
            $this->log->error($e->getMessage());
        }
    }
}
