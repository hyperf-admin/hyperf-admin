<?php
namespace HyperfAdmin\BaseUtils\Model;

use Elasticsearch\Common\Exceptions\Conflict409Exception;
use Hyperf\Elasticsearch\ClientBuilderFactory;
use Hyperf\Utils\Str;
use HyperfAdmin\BaseUtils\Log;

class EsBaseModel
{
    protected $client;

    public $connection = 'default';

    public $index;

    public $type;

    public $logger;

    protected $lastSql;

    protected $primaryKey = 'id';

    protected $fakeDeleteKey = '';

    protected $query;

    // 模糊查询字段
    protected $fuzzy_fields = [];

    // 时间类型字段
    protected $datetime_fields = [];

    // 区间类型字段
    protected $range_fields = [];

    public $mapping;

    protected $all_query;

    public function __construct()
    {
        $builder = container(ClientBuilderFactory::class)->create();
        $server_info = config('es.' . $this->connection);
        if (!$server_info) {
            throw new \Exception(sprintf('elastic connection [%s] not found', $this->connection));
        }
        if (!$this->index) {
            throw new \Exception(sprintf('elastic index is required'));
        }
        $this->client = $builder->setHosts([$server_info])->build();
        $this->logger = Log::get('elastic');
        $this->all_query = [
            'bool' => [
                'must' => [
                    ['match_all' => (object)[]],
                ],
                'must_not' => [],
                'should' => [],
            ],
        ];
    }

    protected $operator_map = [
        '>=' => 'gte',
        '>' => 'gt',
        '=' => 'eq',
        '<=' => 'lte',
        '<' => 'lt',
    ];

    public function select($where = [], $attrs = [], $origin_meta = false)
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => $this->where2query($where) ?: $this->all_query,
            ],
        ];
        $is_scroll = false;
        if (isset($attrs['scroll'])) {
            $is_scroll = true;
            $params['scroll'] = $attrs['scroll'];
        }
        if (isset($attrs['select']) && $attrs['select'] != '*') {
            $params['_source'] = str_replace([' ', '`'], '', $attrs['select']);
        }
        if (isset($attrs['offset'])) {
            $params['body']['from'] = $attrs['offset'];
        }
        if (isset($attrs['limit'])) {
            $params['body']['size'] = $attrs['limit'];
        }
        if (isset($attrs['order_by'])) {
            $order_by = str_replace(['`'], '', $attrs['order_by']);
            $order_by = preg_replace('/ +/', ' ', $order_by);
            $explode = explode(',', $order_by);
            $sorts = [];
            foreach ($explode as $item) {
                if (Str::contains($item, ['+', '-', '*', '/'])) {
                    preg_match('/(\w+) ([+\-*\/]) (\w+) (\w+)/', $item, $m);
                    $sorts[] = [
                        '_script' => [
                            'type' => 'number',
                            'script' => [
                                'lang' => 'painless',
                                'source' => "doc['{$m[1]}'].value {$m[2]} doc['{$m[3]}'].value",
                            ],
                            'order' => $m[4],
                        ],
                    ];
                } else {
                    [
                        $order_by_field,
                        $order_by_type,
                    ] = explode(' ', trim($item));
                    $sorts[] = [
                        $order_by_field => ['order' => $order_by_type],
                    ];
                }
            }
            $params['body']['sort'] = $sorts;
        }
        try {
            $scroll_id = $attrs['scroll_id'] ?? null;
            if (!$scroll_id) {
                $res = $this->client->search($params);
            } else {
                $res = $this->client->scroll([
                    'scroll_id' => $scroll_id,
                    'scroll' => $attrs['scroll'],
                ]);
            }
            $nex_scroll_id = $res['_scroll_id'] ?? null;
            $list = $res['hits']['hits'] ?? [];
            if ($origin_meta) {
                return $list;
            }
            $final = [];
            foreach ($list as $item) {
                $final[] = $item['_source'];
            }
            $this->logger->info('select success', ['params' => json_encode($params, JSON_UNESCAPED_UNICODE)]);
            if ($is_scroll) {
                return [$final, $nex_scroll_id];
            }
            return $final;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('elastic index:%s select error', $this->index), [
                'exception' => $e,
                'params' => $params,
            ]);
            return [];
        }
    }

    public function indices()
    {
        return $this->client->indices();
    }

    public function insert($body)
    {
        $params = [
            'index' => $this->index,
            'type' => $this->type ?: $this->index,
            'body' => $body,
        ];
        if (isset($body[$this->primaryKey])) {
            $params['id'] = $body[$this->primaryKey];
        }
        try {
            return $this->client->index($params);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('elastic index:%s insert error', $this->index), [
                'exception' => $e,
                'params' => $params,
            ]);
            return false;
        }
    }

    public function batchInsert($docs)
    {
        foreach ($docs as $doc) {
            $index = [
                '_index' => $this->index,
                '_type' => $this->type ?: $this->index,
            ];
            if (isset($doc[$this->primaryKey])) {
                $index['_id'] = $doc[$this->primaryKey];
            }
            $params['body'][] = [
                'index' => $index,
            ];
            $params['body'][] = $doc;
        }
        if (!isset($params)) {
            return false;
        }
        try {
            return $this->client->bulk($params);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('elastic index:%s batchInsert error', $this->index), [
                'exception' => $e,
                'params' => $params,
            ]);
            return false;
        }
    }

    public function batchUpdate($docs)
    {
        foreach ($docs as $doc) {
            $index = [
                '_index' => $this->index,
                '_type' => $this->type ?: $this->index,
                '_retry_on_conflict' => 3,
            ];
            if (isset($doc[$this->primaryKey])) {
                $index['_id'] = $doc[$this->primaryKey];
            } else {
                continue;
            }
            $params['body'][] = [
                'update' => $index,
            ];
            $params['body'][] = ['doc' => $doc];
        }
        if (!isset($params)) {
            return false;
        }
        try {
            return $this->client->bulk($params);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('elastic index:%s batchInsert error', $this->index), [
                'exception' => $e,
                'params' => $params,
            ]);
            return false;
        }
    }

    public function batchCreateOrUpdate($docs)
    {
        $ids = array_column($docs, $this->primaryKey);
        if (!$ids) {
            return false;
        }
        $exists = $this->select(['_id' => $ids], [
            'select' => '_none_',
            'limit' => count($ids),
        ], true);
        $exist_ids = array_column($exists, '_id');
        $insert_docs = [];
        $update_docs = [];
        foreach ($docs as $doc) {
            if (in_array($doc[$this->primaryKey], $exist_ids)) {
                $update_docs[] = $doc;
            } else {
                $insert_docs[] = $doc;
            }
        }
        $insert = $this->batchInsert($insert_docs);
        $update = $this->batchUpdate($update_docs);
        return [$insert, $update];
    }

    public function find($id)
    {
        $params = [
            'index' => $this->index,
            'type' => $this->type ?: $this->index,
            'id' => $id,
        ];
        try {
            return $this->client->get($params);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function updateById($id, $doc)
    {
        $update = [
            'index' => $this->index,
            'type' => $this->type ?: $this->index,
            'id' => $id,
            'body' => [
                'doc' => $doc,
            ],
        ];
        try {
            return $this->client->update($update);
        } catch (Conflict409Exception $e) {
            $this->logger->warning(sprintf('elastic index:%s updateById error', $this->index), [
                'exception' => $e,
                'params' => $update,
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('elastic index:%s updateById error', $this->index), [
                'exception' => $e,
                'params' => $update,
            ]);
            return false;
        }
    }

    public function update($where, $doc)
    {
        $docs = $this->select($where, ['select' => 'id,status'], true);
        $ret = [];
        try {
            foreach ($docs as $item) {
                $update = [
                    'index' => $item['_index'],
                    'type' => $item['_type'],
                    'id' => $item['_id'],
                    'body' => [
                        'doc' => $doc,
                    ],
                ];
                $ret[$item['_id']] = $this->client->update($update);
            }
            return $ret;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('elastic index:%s update error', $this->index), [
                'exception' => $e,
                'where' => $where,
            ]);
            return false;
        }
    }

    public function createIndex()
    {
        $params = [
            'index' => $this->index,
        ];
        try {
            if (!$this->client->indices()->exists($params)) {
                $ret = $this->client->indices()->create($params);
                if ($this->mapping) {
                    $this->modifyIndex();
                }
                return $ret;
            } else {
                $this->logger->warning(sprintf('elastic index:%s exists', $this->index));
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('elastic index:%s createIndex error', $this->index), [
                'exception' => $e,
                'params' => $params,
            ]);
            return false;
        }
    }

    public function modifyIndex()
    {
        if (!$this->mapping) {
            return false;
        }
        $params = [
            'index' => $this->index,
            'type' => $this->type ?: $this->index,
            'body' => [
                $this->index => $this->getIndexDefine(),
            ],
        ];
        try {
            return $this->client->indices()->putMapping($params);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('elastic index:%s modifyIndex error', $this->index), [
                'exception' => $e,
                'params' => $params,
            ]);
            return false;
        }
    }

    public function getIndexDefine()
    {
        return $this->mapping;
    }

    public function deleteIndex()
    {
        $deleteParams = [
            'index' => $this->index,
        ];
        try {
            return $this->client->indices()->delete($deleteParams);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('elastic index:%s deleteIndex error', $this->index), [
                'exception' => $e,
                'params' => $deleteParams,
            ]);
            return false;
        }
    }

    public function getMapping()
    {
        $params = [
            'index' => $this->index,
        ];
        try {
            return $this->client->indices()->getMapping($params);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('elastic index:%s getMapping error', $this->index), [
                'exception' => $e,
                'params' => $params,
            ]);
            return false;
        }
    }

    /**
     * 此函数将通用的model where条件平铺, 当前仅支持 and 结合
     *
     * @param $where array
     *
     * @return array
     */
    public function makeTile($where)
    {
        $tmp = [];
        foreach ($where as $k => $v) {
            if (is_numeric($k)) {
                $tmp = array_merge($this->makeTile($v), $tmp);
                continue;
            }
            if (is_string($k)) {
                $k = str_replace(['&/!', '&/'], '', $k);
                if (is_array($v)) {
                    foreach ($v as &$vv) {
                        $vv = str_replace(['&/!', '&/'], '', $vv);
                    }
                    unset($v['__logic'], $vv);
                } else {
                    $v = str_replace(['&/!', '&/'], '', $v);
                }
                $tmp[$k] = $v;
                continue;
            }
        }
        unset($tmp['__logic']);
        return $tmp;
    }

    /**
     * 将 通用where条件 转换为 es 查询query
     *
     * @param $where array
     *
     * @return array
     */
    public function where2query($where)
    {
        //todo 转换场景尚未完全支持
        $query = [];
        $where = $this->makeTile($where);
        if ($this->fakeDeleteKey) {
            $where[$this->fakeDeleteKey] = 0;
        }
        foreach ($where as $key => $val) {
            if (in_array($key, $this->fuzzy_fields)) {
                $kw = $val;
                if (is_array($val) && isset($val['like'])) {
                    $kw = str_replace('%', '', $val['like']);
                }
                $query['bool']['must'][] = [
                    'match_phrase' => [
                        $key => [
                            'query' => $kw,
                            'slop' => 1,
                        ],
                    ],
                ];
                continue;
            }
            if (in_array($key, $this->range_fields)) {
                $is_time_field = in_array($key, $this->datetime_fields);
                if (is_array($val)) {
                    $val_tile = $this->makeTile($val);
                    $index = count($query['bool']['filter']['bool']['must'] ?? []);
                    foreach ($val_tile as $operator => $each) {
                        if (isset($this->operator_map[$operator])) {
                            $mapping_date_format = $this->mapping['properties'][$key]['format'] ?? null;
                            $query['bool']['filter']['bool']['must'][$index]['range'][$key][$this->operator_map[$operator]] = $is_time_field ? date($mapping_date_format ? $this->transDateFormat($mapping_date_format) : "Y-m-d\TH:i:s", strtotime($each)) : $each;
                        }
                    }
                    continue;
                }
            }
            if (is_array($val)) {
                $query['bool']['filter']['bool']['must'][] = [
                    'terms' => [
                        $key => array_map(function ($each) {
                            return is_numeric($each) ? $each * 1 : $each;
                        }, $val),
                    ],
                ];
                continue;
            }
            $query['bool']['filter']['bool']['must'][] = [
                'term' => [
                    $key => is_numeric($val) ? (int)$val : $val,
                ],
            ];
        }
        $this->logger->info(sprintf('elastic %s where2query', $this->index), [
            'where' => $where,
            'query' => $query,
        ]);
        return $query;
    }

    public function selectCount($where)
    {
        try {
            $query = $this->where2query($where);
            $re = $this->_query($query, 0, 1);
            return $re['hits']['total'] ?? 0;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('elastic index:%s selectCount error', $this->index), [
                'exception' => $e,
                'where' => json_encode($where),
                'params' => json_encode($query),
            ]);
            return false;
        }
    }

    public function query($query, $from = null, $size = null)
    {
        $res = $this->_query($query, $from, $size);
        if (empty($res) || empty($res['hits'])) {
            return [];
        }
        $rows = [];
        foreach ($res['hits']['hits'] as $row) {
            $new_row = [];
            foreach ($row['_source'] as $i => $v) {
                $new_row[$i] = $v;
            }
            $rows[] = $new_row;
        }
        return $rows;
    }

    public function count($query)
    {
        $res = $this->_query($query);
        if (empty($res) || empty($res['hits'])) {
            return 0;
        }
        return $res['hits']['total'];
    }

    public function _query($query, $from = null, $size = null)
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => $query ?: $this->all_query,
            ],
        ];
        if (!is_null($from) && !is_null($size)) {
            $params['body']['from'] = $from;
            $params['body']['size'] = $size;
        }
        return $this->client->search($params);
    }

    public function getLastSql()
    {
        return $this->lastSql;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function transDateFormat($es_format_str)
    {
        // yyyy-MM-dd HH:mm:ss
        return str_replace([
            'yyyy',
            'MM',
            'dd',
            'HH',
            'mm',
            'ss',
        ], [
            'Y',
            'm',
            'd',
            'H',
            'i',
            's',
        ], $es_format_str);
    }
}
