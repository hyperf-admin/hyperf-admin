<?php
namespace HyperfAdmin\CronCenter;

use Carbon\Carbon;
use Hyperf\Contract\ConfigInterface;
use Hyperf\DbConnection\Db as DbConnectionDb;
use Hyperf\Utils\ApplicationContext;

class CronManager
{
    /**
     * @var \Hyperf\Contract\ConfigInterface
     */
    private $config;

    private $db;

    public function __construct()
    {
        $container = ApplicationContext::getContainer();
        $this->config = $container->get(ConfigInterface::class);
        $this->db = $this->getConn();
    }

    public function addConfig()
    {
        if ($this->config->has('databases.cron_center')) {
            return;
        }
        $this->config->set('databases.cron_center', [
            'driver' => 'mysql',
            'host' => env('DB_CRON_CENTER_HOST', 'localhost'),
            'database' => env('DB_CRON_CENTER_DATABASE', 'cron_center'),
            'port' => env('DB_CRON_CENTER_PORT', 3306),
            'username' => env('DB_CRON_CENTER_USERNAME', 'root'),
            'password' => env('DB_CRON_CENTER_PASSWORD', 'root'),
            'charset' => env('DB_CRON_CENTER_CHARSET', 'utf8'),
            'collation' => env('DB_CRON_CENTER_COLLATION', 'utf8_unicode_ci'),
            'prefix' => env('DB_CRON_CENTER_PREFIX', ''),
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 10,
                'connect_timeout' => 10.0,
                'wait_timeout' => 3.0,
                'heartbeat' => -1,
                'max_idle_time' => (float)env('DB_MAX_IDLE_TIME', 60),
            ],
            'options' => [
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ],
        ]);
    }

    public function getConn()
    {
        $this->addConfig();

        return DbConnectionDb::connection('cron_center');
    }

    public function getJobResource($just_current_node = true)
    {
        $conn = $this->getConn()->table('cron_jobs');
        $query = $conn->select('*');
        if ($just_current_node) {
            $node_id = $this->getNodeId();
            if (!$node_id) {
                return [];
            }
            $query = $conn->whereRaw("find_in_set({$node_id}, bind_nodes)");
        }
        $list = $query->where('status', 1)->where('is_deleted', 0)->get()->toArray();
        foreach ($list as &$item) {
            $item['config'] = json_decode($item['config'], true);
            $item['state'] = json_decode($item['state'], true);
            $item['alert_rule'] = json_decode($item['alert_rule'], true);
            unset($item);
        }

        return $list;
    }

    public function getAllJobs()
    {
        return $this->getJobResource(false);
    }

    public function getJobs()
    {
        $crons = [];
        $jobs = $this->getJobResource(true);
        foreach ($jobs as $item) {
            $crontab = $this->convertCrontab($item);
            if ($crontab) {
                $crons[] = $crontab;
            }
        }

        return $crons;
    }

    public function getJobById($id)
    {
        $conn = $this->getConn()->table('cron_jobs');
        $job = $conn->select('*')->find($id);
        if (!$job) {
            return false;
        }
        $job['config'] = json_decode($job['config'], true);

        return $this->convertCrontab($job);
    }

    public function convertCrontab($item)
    {
        $config = $item['config'];
        switch ($item['type']) {
            case 'command':
                $callback = array_merge([
                    'command' => $config['execute'],
                ], $config['params']);

                return (new Crontab)->setId($item['id'])
                    ->setType($item['type'])
                    ->setName($item['name'])
                    ->setRule($item['rule'])
                    ->setSingleton($item['singleton'] ?? true)
                    ->setOnOneServer($item['on_one_server'] ?? true)
                    ->setCallback($callback);
                break;
            case 'class':
                $class = explode('::', $config['execute']);
                $callback = [$class[0], 'run', $config['params']];

                return (new Crontab)->setId($item['id'])
                    ->setName($item['name'])
                    ->setRule($item['rule'])
                    ->setSingleton($item['singleton'] ?? true)
                    ->setOnOneServer($item['on_one_server'] ?? false)
                    ->setCallback($callback);
                break;
            case 'gateway':
                $callback = [
                    'api' => $config['api'],
                    'method' => $config['method'] ?? 'GET',
                    'params' => $config['params'] ?? [],
                    'headers' => $config['headers'] ?? [],
                ];

                return (new Crontab)->setId($item['id'])
                    ->setType($item['type'])
                    ->setName($item['name'])
                    ->setRule($item['rule'])
                    ->setSingleton($item['singleton'] ?? true)
                    ->setOnOneServer($item['on_one_server'] ?? false)
                    ->setCallback($callback);
                break;
            default:
                return false;
        }
    }

    public function createOrUpdateNode()
    {
        $node_name = $this->getNodeName();
        $info = $this->config->get('server.settings');
        $nodes = $this->getConn()->table('cron_nodes');
        $data = [
            'name' => $node_name,
            'info' => json_encode($info),
        ];
        $has = $nodes->where(['name' => $node_name])->first();
        if ($has) {
            $data = [
                'status' => $has['status'] === 2 ? 2 : 1,
                'update_at' => date('Y-m-d H:i:s'),
            ];
            $nodes->where(['name' => $node_name])->update($data);
        } else {
            $data['status'] = 1;
            $nodes->insertGetId($data);
        }
    }

    public function blockNode($id)
    {
        $node = $this->getConn()->table('cron_nodes');

        return $node->where('id', $id)->update(['status' => 3]);
    }

    public function getNodeName()
    {
        $port = $this->config->get('server.servers.0.port');
        $host = env("HOST_IP") ?? gethostbyname(gethostname());
        $port = env("HOST_PORT") ?? $port;

        return "{$host}:{$port}";
    }

    public function getNodeId()
    {
        return $this->getConn()->table('cron_nodes')->where('name', $this->getNodeName())->first()['id'];
    }

    public function getAvailableNodes()
    {
        $node = $this->getConn()->table('cron_nodes');

        return $node->where('status', 1)->select(['id', 'name'])->get();
    }

    public function getJobState($id)
    {
        $state = $this->getConn()->table('cron_jobs')->find($id)['state'];

        return $state ? json_decode($state, true) : [];
    }

    public function setJobState($id, $data)
    {
        return $this->getConn()->table('cron_jobs')->where('id', $id)->update(['state' => json_encode($data)]);
    }

    public function dispatch($id)
    {
        $crontab = $this->getJobById($id);
        if (!$crontab) {
            return false;
        }
        $time = Carbon::createFromTimestamp(time() + 10);
        $crontab->setExecuteTime($time);
        $executor = container(Executor::class);
        $ret = $executor->execute($crontab);

        return $ret === null;
    }
}
