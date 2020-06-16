<?php
namespace HyperfAdmin\BaseUtils\Scaffold\Entity;

abstract class EsEntityAbstract implements EntityInterface
{
    protected $model_class;

    /**
     * @var \HyperfAdmin\Util\Scaffold\EsBaseModel
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
        return $this->getModel()->getPrimaryKey();
    }

    public function create($data)
    {
        return $this->getModel()->insert($data);
    }

    public function get($id)
    {
        return $this->getModel()->select(['id' => $id]);
    }

    public function set($id, array $data)
    {
        return false;
    }

    public function delete($id)
    {
        return false;
    }

    public function count($where)
    {
        return $this->getModel()->selectCount($where);
    }

    public function list($where, $attr = [], $page = 1, $size = 20)
    {
        $attr['limit'] = $size;
        $attr['offset'] = ($page - 1) * $size;
        return $this->getModel()->select($where, $attr);
    }

    public function isVersionEnable()
    {
        return false;
    }

    public function lastVersion($version_id = null)
    {
        return [];
    }
}
