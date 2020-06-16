<?php
namespace HyperfAdmin\CronCenter\Install;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;

class InstallCommand extends HyperfCommand
{
    protected $name = 'hyperf-admin:cron-center-install';

    protected function configure()
    {
        $this->setDescription('install db from cron-center.');
    }

    public function handle()
    {
        $db_conf = config('databases.cron_center');
        if (!$db_conf || !$db_conf['host']) {
            $this->output->error('place set cron_center db config in env');
        }

        $sql = file_get_contents(__DIR__ . '/install.sql');

        $re = Db::connection('cron_center')->getPdo()->exec($sql);

        $this->output->success('cron_center db install success');
    }
}
