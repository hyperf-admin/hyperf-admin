<?php
namespace HyperfAdmin\Admin\Service;

use HyperfAdmin\Admin\Model\FrontRoutes;

class Menu
{
    public function query()
    {
        return FrontRoutes::query()->newQuery();
    }

    public function getModuleMenus($module = '', $menu_ids = [])
    {
        $query = $this->query();
        if (!empty($menu_ids)) {
            $query->where(function ($query) use ($menu_ids) {
                return $query->whereIn('id', $menu_ids)->orWhere(function ($query) use ($menu_ids) {
                    return $query->where('is_menu', 0)->whereIn('pid', $menu_ids);
                });
            });
        }
        $query = $query->select([
            'id',
            'pid',
            'label as menu_name',
            'is_menu as hidden',
            'is_scaffold as scaffold',
            'path as url',
            'view',
            'icon',
        ])->where('status', 1);
        if ($module) {
            $query->where('module', $module);
        }
        $list = $query->orderBy('sort', 'desc')->get();
        if (empty($list)) {
            return [];
        }
        $list = $list->toArray();
        foreach ($list as &$item) {
            $item['hidden'] = !(bool)$item['hidden'];
            $item['scaffold'] = (bool)$item['scaffold'];
            unset($item);
        }

        return generate_tree($list);
    }

    public function tree(
        $where = [], $fields = [
        'id as value',
        'pid',
        'label',
    ], $pk_key = 'value'
    ) {
        $where['status'] = 1;
        $query = make(FrontRoutes::class)->where2query($where)->select($fields);
        $list = $query->orderBy('sort', 'desc')->get();
        if (empty($list)) {
            return [];
        }
        $list = $list->toArray();

        return generate_tree($list, 'pid', $pk_key, 'children', function (&$item) use ($pk_key) {
            $item[$pk_key] = (int)$item[$pk_key];
            $item['pid'] = (int)$item['pid'];
            if (isset($item['hidden'])) {
                $item['hidden'] = !(bool)$item['hidden'];
            }
            if (isset($item['scaffold'])) {
                $item['scaffold'] = (bool)$item['scaffold'];
            }
            unset($item);
        });
    }

    public function getPathNodeIds($id)
    {
        $parents = [];
        while ($p = $this->getParent($id)) {
            $id = (int)$p['pid'];
            if ($id) {
                $parents[] = $id;
            }
        }

        return array_reverse($parents);
    }

    public function getParent($id)
    {
        return $this->query()->select(['id', 'pid'])->find($id);
    }
}
