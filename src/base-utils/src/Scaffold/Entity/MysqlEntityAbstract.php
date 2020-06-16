<?php
namespace HyperfAdmin\BaseUtils\Scaffold\Entity;

use Hyperf\DbConnection\Db;
use Hyperf\Utils\Str;

abstract class MysqlEntityAbstract implements EntityInterface
{
    protected $model_class;

    /**
     * @var \HyperfAdmin\Util\Scaffold\BaseModel
     */
    protected $model;

    public function __construct($model_class = '')
    {
        if ($model_class) {
            $this->model = make($model_class);
        }
    }

    public function getModel()
    {
        if ($this->model) {
            return $this->model;
        }
        if ($this->model_class) {
            $this->model = make($this->model_class);
        }
        return $this->model;
    }

    public function getPk()
    {
        return $this->getModel()::getPrimaryKey();
    }

    public function create($data)
    {
        $entity = $this->getModel()->fill($data);
        $entity->save();
        return $entity->{$this->getPk()};
    }

    public function get($id)
    {
        return $this->getModel()->where($this->getPk(), $id)->firstAsArray();
    }

    public function set($id, array $data)
    {
        $record = $this->getModel()->where($this->getPk(), $id)->first();
        if (!$record) {
            return false;
        }
        return $record->fill($data)->save();
    }

    public function delete($id)
    {
        return $this->getModel()->destroy($id);
    }

    public function count($where)
    {
        return $this->getModel()->where2query($where)->count();
    }

    public function list($where, $attr = [], $page = 1, $size = 20)
    {
        $query = $this->getModel()->where2query($where);
        if ($attr['select'] ?? false) {
            $selects = array_map(function ($select) {
                $select = trim($select);
                if (Str::contains($select, ' ')) {
                    return Db::connection($this->getModel()->getConnectionName())->raw($select);
                } else {
                    return $select;
                }
            }, $attr['select']);
            $query->select($selects);
        }
        if (isset($attr['order_by'])) {
            $query->orderByRaw($attr['order_by']);
        }
        if (isset($attr['group_by'])) {
            // todo groupByRaw
            $query->groupBy($attr['group_by']);
        }
        $query->limit($size)->offset(($page - 1) * $size);
        $ret = $query->get();
        return $ret ? $ret->toArray() : [];
    }

    public function isVersionEnable()
    {
        $version_enable = false;
        if (method_exists($this->getModel(), 'isVersionEnable')) {
            $version_enable = $this->getModel()->isVersionEnable();
        }
        return $version_enable;
    }

    public function lastVersion($version_id = null)
    {
        return [];
    }
}
