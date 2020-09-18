<?php
namespace HyperfAdmin\Admin\Install;

use Composer\Semver\Comparator;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Composer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UpdateCommand extends HyperfCommand
{
    protected $name = 'hyperf-admin:admin-update';

    protected function configure()
    {
        $this->setDescription('update db for hyperf-admin.')
            ->addArgument('version', InputArgument::REQUIRED, 'the update db version.');
    }

    public function handle()
    {
        $version = $this->input->getArgument('version');
        $db_conf = config('databases.hyperf_admin');
        if (!$db_conf || !$db_conf['host']) {
            $this->output->error('place set hyperf_admin db config in env');
            return 1;
        }

        $update_sql_file = __DIR__ . "/update_{$version}.sql";

        if (!file_exists($update_sql_file)) {
            $this->output->error("the version {$version} file not found");
            return 1;
        }

        $sql = file_get_contents($update_sql_file);

        $re = Db::connection('hyperf_admin')->getPdo()->exec($sql);

        $this->output->success('hyperf-admin db update success');

    }
}
