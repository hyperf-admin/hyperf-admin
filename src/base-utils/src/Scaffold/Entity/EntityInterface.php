<?php
namespace HyperfAdmin\BaseUtils\Scaffold\Entity;

interface EntityInterface
{
    public function create(array $data);

    public function set($id, array $data);

    public function get($id);

    public function delete($id);

    public function count($where);

    public function list($where, $attr = [], $page = 1, $size = 20);

    public function getPk();

    public function isVersionEnable();
}
