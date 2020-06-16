<?php
namespace HyperfAdmin\Admin\Install;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;

class InstallCommand extends HyperfCommand
{
    protected $name = 'hyperf-admin:admin-install';

    protected function configure()
    {
        $this->setDescription('install db from hyperf-admin.');
    }

    public function handle()
    {
        $db_conf = config('databases.hyperf_admin');
        if (!$db_conf || !$db_conf['host']) {
            $this->output->error('place set hyperf_admin db config in env');
        }

        $sql = file_get_contents(__DIR__ . '/install.sql');

        $re = Db::connection('hyperf_admin')->getPdo()->exec($sql);

        $this->output->success('hyperf-admin db install success');
    }
}
