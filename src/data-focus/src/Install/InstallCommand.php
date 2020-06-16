<?php
namespace HyperfAdmin\DataFocus\Install;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;

class InstallCommand extends HyperfCommand
{
    protected $name = 'hyperf-admin:data-focus-install';

    protected function configure()
    {
        $this->setDescription('install db from data-focus.');
    }

    public function handle()
    {
        $db_conf = config('databases.data_focus');
        if (!$db_conf || !$db_conf['host']) {
            $this->output->error('place set data_focus db config in env');
        }

        $sql = file_get_contents(__DIR__ . '/install.sql');

        $re = Db::connection('cron_center')->getPdo()->exec($sql);

        $this->output->success('data_focus db install success');
    }
}
