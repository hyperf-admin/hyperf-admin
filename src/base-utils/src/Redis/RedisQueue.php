<?php
namespace HyperfAdmin\BaseUtils\Redis;

use Hyperf\Redis\RedisFactory;

class RedisQueue
{
    protected $cluster = 'default';

    protected $queue_name = 'redis_queue_default';

    protected $queue_wait_ack_suffix = 'wait_ack';

    protected $wait_timeout = 10;

    protected $max = 100000;

    /**
     * @var \Co\Redis
     */
    protected $redis;

    public function __construct($queue_name, $cluster = 'default')
    {
        $this->queue_name = $queue_name;
        $this->cluster = $cluster;
        $this->redis = container(RedisFactory::class)->get($cluster);
    }

    public function length($name = '')
    {
        return $this->redis->lLen($name ?: $this->queue_name);
    }

    public function push(array $msg)
    {
        if($this->length() < $this->max) {
            return $this->redis->rPush($this->queue_name, json_encode($msg));
        } else {
            $that = $this;

            return retry(3, function () use ($that, $msg) {
                $that->push($msg);
            }, 3);
        }
    }

    public function pop($filter = [])
    {
        $un_ack = $this->unAck();
        foreach($un_ack as $index => $item) {
            if(($item['_time'] + $this->wait_timeout) < time()) {
                unset($item['_time']);
                $msg = $item;
                $item['_time'] = time();
                $this->redis->lSet($this->getAckQueueName(), $index, json_encode($item));
                if($filter) {
                    if(array_intersect($item, $filter)) {
                        return $item;
                    }
                } else {
                    return $msg;
                }
            }
        }
        $msg = $this->getOne($this->queue_name, $filter);
        if($msg) {
            $msg_id = md5($msg);
            $data = json_decode($msg, true);
            $ret = array_merge($data, ['_queue_msg_id' => $msg_id]);
            $this->redis->rPush($this->getAckQueueName(), json_encode(array_merge($ret, ['_time' => time()])));

            return $ret;
        }

        return null;
    }

    public function getOne($queue_name, $filter = [])
    {
        if(!$filter) {
            return $this->redis->lPop($queue_name);
        }
        $len = $this->redis->lLen($queue_name);
        if(!$len) {
            return null;
        }
        foreach(range(0, $len - 1) as $index) {
            $ele = $this->redis->lIndex($queue_name, $index);
            $ele_array = json_decode($ele, true);
            if(array_intersect($ele_array, $filter)) {
                $this->redis->lRem($queue_name, $ele, 1);

                return $ele;
            }
        }

        return null;
    }

    protected function clearQueue($queue_name)
    {
        $length = $this->length($queue_name);

        return $this->redis->ltrim($queue_name, $length + 1, $length + 2);
    }

    public function clear()
    {
        return $this->clearQueue($this->queue_name) && $this->clearQueue($this->getAckQueueName());
    }

    public function getAckQueueName()
    {
        return $this->queue_name . '_' . $this->queue_wait_ack_suffix;
    }

    public function ack($msg_id)
    {
        $un_ack = $this->unAck();
        $ret = false;
        foreach($un_ack as $item) {
            if($item['_queue_msg_id'] == $msg_id) {
                $ret = $this->redis->lRem($this->getAckQueueName(), json_encode($item), 0);
            }
        }

        return $ret;
    }

    public function unAck()
    {
        $list = $this->redis->lRange($this->getAckQueueName(), 0, -1);
        foreach($list as &$item) {
            $item = json_decode($item, true);
        }
        unset($item);

        return $list;
    }

    public function unAckCount()
    {
        return $this->length($this->getAckQueueName());
    }
}
