<?php
namespace HyperfAdmin\BaseUtils\Model;

use Hyperf\DbConnection\Model\Model;
use Yadakhov\InsertOnDuplicateKey;

/**
 * @mixin \Hyperf\Database\Model\Builder
 * @mixin \Hyperf\Database\Query\Builder
 * @method static where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static select($columns = ['*'])
 */
class BaseModel extends Model
{
    use InsertOnDuplicateKey;

    const CREATED_AT = MODEL_CREATED_AT_FIELD;

    const UPDATED_AT = MODEL_UPDATED_AT_FIELD;

    const STATUS_YES = YES;

    const STATUS_NOT = NO;

    public static $status = [
        self::STATUS_YES => '启用',
        self::STATUS_NOT => '禁用',
    ];

    /**
     * @param array                               $where
     * @param null|\Hyperf\Database\Query\Builder $query
     *
     * @return \Hyperf\Database\Query\Builder
     */
    public function where2query($where, $query = null)
    {
        $query = $query ?? $this->newQuery();
        if (!$where) {
            return $query;
        }
        $boolean = strtolower($where['__logic'] ?? 'and');
        unset($where['__logic']);
        foreach ($where as $key => $item) {
            if (is_numeric($key) && is_array($item)) {
                $query->where(function ($query) use ($item) {
                    return $this->where2query($item, $query);
                }, null, null, $boolean);
                continue;
            }
            if (!is_array($item)) {
                $query->where($key, '=', $item, $boolean);
                continue;
            }
            if (is_real_array($item)) {
                $query->whereIn($key, $item, $boolean);
                continue;
            }
            foreach ($item as $op => $val) {
                if ($op == 'not in' || $op == 'not_in') {
                    $query->whereNotIn($key, $val, $boolean);
                    continue;
                }
                if ($op == 'like') {
                    $query->where($key, 'like', $val, $boolean);
                    continue;
                }
                if ($op == 'between') {
                    $query->whereBetween($key, $val, $boolean);
                    continue;
                }
                if ($op == 'find_in_set') { // and or
                    $query->where(function ($q) use ($val, $key) {
                        if (!is_array($val)) {
                            $val = ['values' => $val, 'operator' => 'and'];
                        }
                        $operator = $val['operator'];
                        $method = ($operator === 'or' ? 'or' : '') . "whereRaw";
                        foreach ($val['values'] as $set_val) {
                            $q->{$method}("find_in_set({$set_val}, {$key})");
                        }
                    });
                    continue;
                }
                $query->where($key, $op, $val, $boolean);
            }
        }
        return $query;
    }

    /**
     * select options 通用搜索底层方法
     *
     * @param array          $attr
     * @param array          $extra_where
     * @param string         $name_key
     * @param string|integer $id_key
     * @param string         $logic
     * @param bool           $default_query
     *
     * @return array
     */
    public function search($attr, $extra_where = [], $name_key = 'name', $id_key = 'id', $logic = 'and', $default_query = false)
    {
        $where = [];
        $kw = request()->input('kw');
        if ($kw) {
            if (preg_match_all('/^\d+$/', $kw)) {
                $where[$id_key] = $kw;
            } elseif (preg_match_all('/^(\d+,?)+$/', $kw)) {
                $where[$id_key] = explode(',', $kw);
            } else {
                $where[$name_key] = ['like' => "%{$kw}%"];
            }
        }
        $id = request()->input('id');
        if ($id) {
            if (preg_match_all('/^\d+$/', $id)) {
                $where[$id_key] = $id;
            } elseif (preg_match_all('/^(\d+,?)+$/', $id)) {
                $where[$id_key] = explode(',', $id);
            }
        }
        if (!$default_query && !$where) {
            return [];
        }
        $where['__logic'] = $logic;
        $where = array_merge($where, $extra_where);
        $attr['limit'] = $attr['limit'] ?? 100;
        return $this->list($where, $attr)->toArray();
    }

    public function list($where, array $attr)
    {
        $query = $this->where2query($where);
        if (isset($attr['select'])) {
            $query = $query->select($attr['select']);
        }
        if (isset($attr['select_raw'])) {
            $query = $query->selectRaw($attr['select_raw']);
        }
        $order_by = $attr['order_by'] ?? '';
        if ($order_by) {
            $query = $query->orderByRaw($order_by);
        }
        if (isset($attr['limit'])) {
            $query = $query->limit($attr['limit']);
        }
        return $query->get();
    }
}
