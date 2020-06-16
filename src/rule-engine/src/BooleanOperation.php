<?php
namespace HyperfAdmin\RuleEngine;

class BooleanOperation
{
    protected $data = [];

    protected $sets = [];

    protected $info = [];

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public function setContext($data)
    {
        $this->data = $data;
    }

    public function clearContext()
    {
        $this->data = [];
    }

    public function execute($filters)
    {
        $this->info = [];
        $this->sets = [];
        if(!isset($filters[0]) || !is_array($filters[0])) {
            throw new FilterException('filter error');
        }
        if(is_array($filters[0][0])) {
            $ret = [];
            foreach($filters as $filter) {
                $ret_cell = $this->cellParse($filter);
                if($ret_cell !== false) {
                    $ret = array_merge($ret, $ret_cell);
                }
            }
        } else {
            $ret = $this->cellParse($filters);
        }

        return $ret;
    }

    protected function cellParse(array $cell)
    {
        if(!$cell) {
            return false;
        }
        $logic = strtolower($cell['__logic'] ?? 'and');
        unset($cell['__logic']);
        if(count($cell) === 1) {
            $rules = $cell;
            $sets = [];
        } else {
            $sets = array_pop($cell);
            $rules = $cell;
        }
        $check_ret = [];
        foreach($rules as $rule) {
            $check = $this->compare($rule[1], ArrayHelper::array_get($rule[0], $this->data), $rule[2]);
            $this->info[] = [
                'execute' => $rule,
                'result' => $check,
            ];
            if($logic == 'and' && $check === false) {
                return false;
            }
            if($logic == 'or') {
                $check_ret[] = $check;
            }
        }
        if($logic == 'or') {
            $check_ret = (bool)array_sum(array_unique($check_ret));
            if($check_ret === false) {
                return false;
            }
        }
        if(ArrayHelper::array_depth($sets) === 1) {
            $sets && $this->assignment(...$sets);
        } else {
            foreach($sets as $set) {
                $this->assignment(...$set);
            }
        }

        return $this->sets;
    }

    /**
     * @param string              $operator
     * @param string|number|array $current_val 用户实际值
     * @param string|number       $rule_val    filter限定值/范围
     *
     * @return bool
     * @throws
     */
    protected function compare($operator, $current_val, $rule_val)
    {
        switch($operator) {
            case '=':
            case 'is':
                return $current_val == $rule_val;
            case '>':
                return $current_val > $rule_val;
            case '>=':
                return $current_val >= $rule_val;
            case '<':
                return $current_val < $rule_val;
            case '<=':
                return $current_val <= $rule_val;
            case '!=':
                return $current_val != $rule_val;
            case 'in':
                return in_array($current_val, $this->toArr($rule_val));
            case 'not in':
                return !in_array($current_val, $this->toArr($rule_val));
            case 'between':
                $rule = $this->toArr($rule_val);
                if($this->isDateStr($rule[0])) {
                    $rule[0] = strtotime($rule[0]);
                    $rule[1] = strtotime($rule[1]);
                }

                return $current_val > $rule[0] && $current_val < $rule[1];
            case '~':
                return (bool)preg_match($rule_val, $current_val);
            // 动态时间比较 变量值为时间戳，入参值：为过去了多少秒；eg："3600" 表示变量值经过了一个小时
            case 'elapse':
                return (time() - $rule_val) > $this->isDateStr($rule_val) ? strtotime($current_val) : $current_val;
            // 动态时间区间 变量值为时间戳，入参值：为过去了多少秒区间; eg:"3600,7200" 表示经过了在一个小时和两个小时之间
            case 'elapse between':
                $rule = $this->toArr($rule_val);
                if($this->isDateStr($rule[0])) {
                    return (time() - $rule_val) > strtotime($rule[0]) && (time() - $rule_val) < strtotime($rule[1]);
                }

                return (time() - $rule_val) > $rule[0] && (time() - $rule_val) < $rule[1];
            case 'has':
                return (bool)array_intersect($this->toArr($rule_val), $current_val);
            case 'none':
                return !array_intersect($this->toArr($rule_val), $current_val);
            default:
                throw new FilterException("the compare operator '{$operator}' not support");
        }
    }

    protected function toArr($val)
    {
        if(is_array($val)) {
            return $val;
        }
        if(strpos($val, ',')) {
            return explode(',', $val);
        }
        if(strpos($val, '...')) {
            $parts = explode('...', $val);

            return range($parts[0], $parts[1]);
        }

        return [];
    }

    protected function isDateStr($var)
    {
        return preg_match('/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]/', $var);
    }

    protected function assignment($key, $operator, $right)
    {
        $left = ArrayHelper::array_get($key, $this->sets);
        switch($operator) {
            case '=':
                $ret = $right;
                break;
            case '+';
                $ret = $left + $right;
                break;
            case '-':
                $ret = $left - $right;
                break;
            case '*':
                $ret = $left * $right;
                break;
            case '÷':
            case '/':
                $ret = $left / $right;
                break;
            case 'rm':
                array_remove($this->sets, $key);
                $ret = '--';
                break;
            default:
                throw new FilterException("the assignment operator '{$operator}' not support");
        }
        if($ret !== '--') {
            return ArrayHelper::array_set($this->sets, $key, $ret);
        }
    }

    public function getInfo()
    {
        return $this->info;
    }
}
