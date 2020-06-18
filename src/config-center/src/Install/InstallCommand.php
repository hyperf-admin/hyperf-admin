<?php
namespace HyperfAdmin\ConfigCenter\Install;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;

class InstallCommand extends HyperfCommand
{
    protected $name = 'hyperf-admin:config-center-install';

    protected function configure()
    {
        $this->setDescription('install db from cron-center.');
    }

    public function handle()
    {
        $db_conf = config('databases.config_center');
        if (!$db_conf || !$db_conf['host']) {
            $this->output->error('place set config_center db config in env');
        }

        $sql = file_get_contents(__DIR__ . '/install.sql');

        $re = Db::connection('config_center')->getPdo()->exec($sql);

        $this->output->success('config_center db install success');
    }
}
