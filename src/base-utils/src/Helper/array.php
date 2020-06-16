<?php
if(!function_exists('array_group_k2k')) {
    function array_group_k2k(array $items, $key1, $key2 = null)
    {
        $map = [];
        foreach($items as $item) {
            $map[$item[$key1]][] = $key2 ? $item[$key2] : $item;
        }

        return $map;
    }
}
if(!function_exists('array_group_by')) {
    function array_group_by(array $arr, $key)
    {
        $grouped = [];
        foreach($arr as $value) {
            $grouped[$value[$key]][] = $value;
        }
        // Recursively build a nested grouping if more parameters are supplied
        // Each grouped array value is grouped according to the next sequential key
        if(func_num_args() > 2) {
            $args = func_get_args();
            foreach($grouped as $key => $value) {
                $parms = array_merge([$value], array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array('array_group_by', $parms);
            }
        }

        return $grouped;
    }
}

if(!function_exists('array_node_append')) {
    function array_node_append($list, $key, $append_key, $callable)
    {
        $kws = array_column($list, $key);
        $ret = $callable($kws);
        foreach($list as &$item) {
            $item[$append_key] = $ret[$item[$key]] ?? '';
            unset($item);
        }

        return $list;
    }
}
if(!function_exists('array_map_recursive')) {
    function array_map_recursive(callable $func, array $data)
    {
        $result = [];
        foreach($data as $key => $val) {
            $result[$key] = is_array($val) ? array_map_recursive($func, $val) : call($func, [$val]);
        }

        return $result;
    }
}

if(!function_exists('array_copy')) {
    function array_copy($arr, $keys = [])
    {
        if(!$keys) {
            return $arr;
        }
        $new = [];
        foreach($keys as $index => $key) {
            $new_key = is_string($index) ? $index : $key;
            isset($arr[$key]) && $new[$new_key] = $arr[$key];
        }

        return $new;
    }
}

if(!function_exists('array_sort_by_key_length')) {
    function array_sort_by_key_length($arr, $sort_order = SORT_DESC)
    {
        $keys = array_map('strlen', array_keys($arr));
        array_multisort($keys, $sort_order, $arr);

        return $arr;
    }
}

if(!function_exists('array_sort_by_value_length')) {
    function array_sort_by_value_length($arr, $sort_order = SORT_DESC)
    {
        $keys = array_map('strlen', $arr);
        array_multisort($keys, $sort_order, $arr);

        return $arr;
    }
}

if(!function_exists('array_to_kv')) {
    function array_to_kv($arr, $as_key_column, $as_value_column)
    {
        $new = [];
        foreach($arr as $item) {
            $new[$item[$as_key_column]] = $item[$as_value_column];
        }

        return $new;
    }
}

if(!function_exists('array_flat')) {
    /**
     * 将数组递归展开至深度为1的新数组
     *
     * @param      $arr
     * @param bool $keep_key
     *
     * @return array|mixed
     */
    function array_flat($arr, $keep_key = true)
    {
        $newArr = [];
        if($keep_key) {
            foreach($arr as $key => $item) {
                if(is_array($item)) {
                    $newArr = $newArr + array_flat($item, true);
                } else {
                    $newArr[$key] = $item;
                }
            }
        } else {
            foreach($arr as $item) {
                if(is_array($item)) {
                    $newArr = array_merge($newArr, array_flat($item));
                } else {
                    $newArr[] = $item;
                }
            }
        }

        return $newArr;
    }
}

if(!function_exists('array_depth')) {
    function array_depth(array $array)
    {
        $max_depth = 1;
        foreach($array as $value) {
            if(is_array($value)) {
                $depth = array_depth($value) + 1;
                if($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }

        return $max_depth;
    }
}

if(!function_exists('array_merge_node')) {
    function array_merge_node($arr, $node, $key)
    {
        $box = [];
        foreach($arr as $each) {
            $box[$each[$key] . '-'] = $each;
        }
        if(isset($node[0])) {
            foreach($node as $n) {
                $box[$n[$key] . '-'] = $n;
            }
        } else {
            $box[$node[$key] . '-'] = $node;
        }

        return array_values($box);
    }
}

if(!function_exists('array_change_v2k')) {
    /**
     * 将二维数组二维某列的key值作为一维的key
     *
     * @param array  $arr    原始数组
     * @param string $column key
     */
    function array_change_v2k(&$arr, $column)
    {
        if(empty($arr)) {
            return;
        }
        $new_arr = [];
        foreach($arr as $val) {
            $new_arr[$val[$column]] = $val;
        }
        $arr = $new_arr;
    }
}

if(!function_exists('array_group')) {
    function array_group($arr, $key)
    {
        $tmp = [];
        foreach($arr as $item) {
            $tmp[$item[$key]][] = $item;
        }

        return $tmp;
    }
}

if(!function_exists('array_last')) {
    function array_last($arr)
    {
        return $arr[count($arr) - 1];
    }
}

if(!function_exists('array_split')) {
    /**
     * 将数组切割成几份
     *
     * @param array $arr         需要分割的数组
     * @param int   $chunk_count 需要分割成几份
     *
     * @return array
     */
    function array_split(array $arr, int $chunk_count)
    {
        $total = count($arr);
        if($chunk_count >= $total) {
            return array_chunk($arr, 1);
        }
        $remainder = array_splice($arr, 0, $total % $chunk_count);
        $chunks = array_chunk($arr, (int)($total / $chunk_count));
        $i = 0;
        while($remainder) {
            array_push($chunks[$i++], array_shift($remainder));
        }

        return $chunks;
    }
}

if(!function_exists('array_get_by_keys')) {
    function array_get_by_keys($arr, $keys = [])
    {
        if(!$keys) {
            return $arr;
        }
        $tmp = [];
        foreach($keys as $key) {
            $tmp[$key] = $arr[$key] ?? null;
        }

        return $tmp;
    }
}

if(!function_exists('array_remove')) {
    function array_remove($arr, $del)
    {
        if(($key = array_search($del, $arr)) !== false) {
            unset($arr[$key]);
        }

        return array_merge($arr);
    }
}

if(!function_exists('array_get_node')) {
    function array_get_node($key, $arr = [])
    {
        $path = explode('.', $key);
        foreach($path as $key) {
            $key = trim($key);
            if(empty($arr) || !isset($arr[$key])) {
                return null;
            }
            $arr = $arr[$key];
        }

        return $arr;
    }
}

if(!function_exists('array_remove_keys_not_in')) {
    function array_remove_keys_not_in($arr, $keys)
    {
        return array_remove_keys($arr, array_diff(array_keys($arr), $keys));
    }
}

if(!function_exists('array_remove_keys')) {
    function array_remove_keys($arr, $keys)
    {
        foreach($keys as $key) {
            unset($arr[$key]);
        }

        return $arr;
    }
}

if(!function_exists('array_overlay')) {
    /**
     * 数组合并: 使用 $source 中的值覆盖 $target 中的值
     *
     * @param array $source
     * @param array $target
     *
     * @return array
     */
    function array_overlay($source, $target)
    {
        if(!is_array($source)) {
            return $target;
        }
        foreach($source as $key => $val) {
            if(!is_array($val) || !isset($target[$key]) || !is_array($target[$key])) {
                $target[$key] = $val;
            } else {
                $target[$key] = array_overlay($val, $target[$key]);
            }
        }

        return $target;
    }
}
