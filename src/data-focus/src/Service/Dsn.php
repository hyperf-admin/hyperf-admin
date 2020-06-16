<?php
namespace HyperfAdmin\DataFocus\Service;

use Hyperf\Contract\ConfigInterface;
use HyperfAdmin\DataFocus\Model\Dsn as DsnModel;
use HyperfAdmin\BaseUtils\Redis\Redis;

class Dsn
{
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function initAll()
    {
        $dsn_list = DsnModel::query()->where('status', '=', DsnModel::STATUS_YES)->select([
                'name',
                'type',
                'config',
            ])->get()->toArray();
        // todo decrypt
        //$dsn_list = $this->config->decryptOne($dsn_list);
        foreach($dsn_list as $dsn) {
            $method = 'add' . strtolower(DsnModel::$types[$dsn['type']]);
            if(method_exists($this, $method)) {
                $this->{$method}($dsn['name'], $dsn['config']);
            }
        }
    }

    public function addToConfig($id)
    {
        $this->changeConfig($id, 'add');
    }

    public function removeFromConfig($id)
    {
        $this->changeConfig($id, 'remove');
    }

    public function changeConfig($id, $type)
    {
        $dsn = DsnModel::query()
            ->select(['name', 'type', 'config'])
            ->where('id', $id)
            ->firstAsArray();
        $dsn = $this->config->decryptOne($dsn);
        if(!$dsn) {
            return false;
        }
        $method = $type . strtolower(DsnModel::$types[$dsn['type']]);
        $this->{$method}($dsn['name'], $dsn['config']);
    }

    public function addmysql($name, $conf)
    {
        $this->config->set('databases.data_focus_' . $name, db_complete($conf));
    }

    public function removemysql($name)
    {
        // TODO Rewrite core/Util/Config.php
        //$this->config->unset('database.data_focus_' . $name);
    }

    public function addredis($name, $conf)
    {
        $this->config->set('redis.data_focus_' . $name, $conf);
    }

    public function removeredis($name)
    {
    }

    public static function getChanged()
    {
        $key = 'data_focus:' . php_uname('n') . ':dsn_worker_sync_last_time';
        $last_check_time = Redis::get($key) ?: date('Y-m-d H:i:s');
        $changed = DsnModel::query()
            ->select(['id'])
            ->where('update_at', '>=', $last_check_time)
            ->where('status', DsnModel::STATUS_YES)
            ->getAsArray();
        if(!$changed) {
            return null;
        }
        Redis::set($key, date('Y-m-d H:i:s'));

        return array_column($changed, 'id');
    }

    public static function changedSetToConfig($ids)
    {
        $self = make(Dsn::class);
        foreach($ids as $id) {
            $self->addToConfig($id);
        }
    }
}
