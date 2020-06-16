<?php
namespace HyperfAdmin\BaseUtils\Scaffold\Entity;

abstract class ApiEntityAbstract implements EntityInterface
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
        return $this->getModel()->insertGetId($data);
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
            $query->select($attr['select']);
        }
        if ($attr['order_by']) {
            $query->orderByRaw($attr['order_by']);
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
