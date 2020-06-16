<?php
namespace HyperfAdmin\Validation;

use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
use Hyperf\Validation\Contract\PresenceVerifierInterface;
use Hyperf\Validation\Contract\Rule;
use Hyperf\Validation\ValidatorFactory;

class Validation
{
    use ValidationCustomRule;

    /** @var ValidatorFactory */
    public $factory;

    public function __construct()
    {
        $this->factory = make(ValidatorFactory::class);
    }

    public function check($rules, $data, $obj = null, $options = [])
    {
        foreach($data as $key => $val) {
            if(strpos($key, '.') !== false) {
                Arr::set($data, $key, $val);
                unset($data[$key]);
            }
        }
        $map = [];
        $real_rules = [];
        $white_data = [];

        foreach($rules as $key => $rule) {
            $field_extra = explode('|', $key);
            $field = $field_extra[0];
            if(!$rule && Arr::get($data, $field)) {
                $white_data[$field] = Arr::get($data, $field);
                continue;
            }
            $title = $field_extra[1] ?? $field_extra[0];
            $rules = is_array($rule) ? $rule : explode('|', $rule);
            foreach($rules as $index => &$item) {
                if($index === 'children') {
                    $request_sub_data = Arr::get($data, $field);
                    if($item['repeat']) {
                        foreach($request_sub_data as $part_index => $part) {
                            [
                                $sub_data,
                                $sub_error,
                            ] = $this->check($item['rules'], $part);
                            if($sub_error) {
                                $sub_error[0] = $title . '的第' . ($part_index + 1) . '项 ' . $sub_error[0];

                                return [$sub_data, $sub_error];
                            }
                        }
                    } else {
                        [
                            $sub_data,
                            $sub_error,
                        ] = $this->check($item, $request_sub_data);
                        if($sub_error) {
                            $sub_error[0] = $title . '中的 ' . $sub_error[0];

                            return [$sub_data, $sub_error];
                        }
                    }
                    continue;
                }
                if($item == 'json') {
                    $item = 'array';
                }
                if(method_exists($this, $item)) {
                    $item = $this->makeCustomRule($item);
                } elseif(is_string($item) && Str::startsWith($item, 'call_')) {
                    $item = $this->makeCustomRule(Str::replaceFirst('call_', '', $item));
                } elseif(is_string($item) && Str::startsWith($item, 'cb_')) {
                    $item = $this->makeObjectCallback(Str::replaceFirst('cb_', '', $item), $obj);
                }
                unset($item);
            }
            $real_rules[$field] = $rules;
            $map[$field] = $title;
        }

        $validator = $this->factory->make($data, $real_rules);
        $verifier = container(PresenceVerifierInterface::class);
        $validator->setPresenceVerifier($verifier);

        $fails = $validator->fails();
        $errors = [];
        if($fails) {
            $errors = $validator->errors()->all();
            foreach($errors as &$item) {
                $filed_keys = array_keys($map);
                $filed_keys = array_sort_by_value_length($filed_keys);
                $replace = [];
                foreach($filed_keys as $k) {
                    $replace[] = $map[$k];
                }
                $map = array_sort_by_key_length($map);
                $filed_keys = array_map(function ($key) {
                    if(strpos($key, '.') === false) {
                        return str_replace('_', ' ', $key);
                    }

                    return $key;
                }, $filed_keys);
                if(preg_match('/.*当 (.*) 是 (.*)/', $item, $m)) {
                    if(isset($m[1]) && isset($m[2])) {
                        $field = str_replace(' ', '_', $m[1]);
                        $option = $options[$field][$m[2]];
                        $item = preg_replace('/是 .*/', '是 ' . $option, $item);
                    }
                }

                $item = str_replace($filed_keys, $replace, $item);
                $item = str_replace('字段', '', $item);
                unset($item);
            }

            return [
                null,
                $errors,
            ];
        }

        $filter_data = array_merge($this->parseData($validator->validated()), $white_data);

        $real_data = [];
        foreach($filter_data as $key => $val) {
            Arr::set($real_data, $key, $val);
        }

        $real_data = array_map_recursive(function ($item) {
            return is_string($item) ? trim($item) : $item;
        }, $real_data);

        return [
            $fails ? null : $real_data,
            $errors,
        ];
    }

    public function makeCustomRule($custom_rule)
    {
        return new class ($custom_rule, $this) implements Rule {
            public $custom_rule;

            public $validation;

            public $error = "%s ";

            public $attribute;

            public function __construct($custom_rule, $validation)
            {
                $this->custom_rule = $custom_rule;
                $this->validation = $validation;
            }

            public function passes($attribute, $value): bool
            {
                $this->attribute = $attribute;
                $rule = $this->custom_rule;
                if(strpos($rule, ':') !== false) {
                    $rule = explode(':', $rule)[0];
                    $extra = explode(',', explode(':', $rule)[1]);
                    $ret = $this->validation->$rule($attribute, $value, $extra);
                    if(is_string($ret)) {
                        $this->error .= $ret;

                        return false;
                    }

                    return true;
                }
                $ret = $this->validation->$rule($attribute, $value);
                if(is_string($ret)) {
                    $this->error .= $ret;

                    return false;
                }

                return true;
            }

            public function message()
            {
                return sprintf($this->error, $this->attribute);
            }
        };
    }

    public function makeObjectCallback($method, $object)
    {
        return new class ($method, $this, $object) implements Rule {
            public $custom_rule;

            public $validation;

            public $object;

            public $error = "%s ";

            public $attribute;

            public function __construct($custom_rule, $validation, $object)
            {
                $this->custom_rule = $custom_rule;
                $this->validation = $validation;
                $this->object = $object;
            }

            public function passes($attribute, $value): bool
            {
                $this->attribute = $attribute;
                $rule = $this->custom_rule;
                if(strpos($rule, ':') !== false) {
                    $rule = explode(':', $rule)[0];
                    $extra = explode(',', explode(':', $rule)[1]);
                    $ret = $this->object->$rule($attribute, $value, $extra);
                    if(is_string($ret)) {
                        $this->error .= $ret;

                        return false;
                    }

                    return true;
                }
                $ret = $this->object->$rule($attribute, $value);
                if(is_string($ret)) {
                    $this->error .= $ret;

                    return false;
                }

                return true;
            }

            public function message()
            {
                return sprintf($this->error, $this->attribute);
            }
        };
    }

    /**
     * Parse the data array, converting -> to dots
     */
    public function parseData(array $data): array
    {
        $newData = [];

        foreach($data as $key => $value) {
            if(is_array($value)) {
                $value = $this->parseData($value);
            }

            if(Str::contains((string)$key, '->')) {
                $newData[str_replace('->', '.', $key)] = $value;
            } else {
                $newData[$key] = $value;
            }
        }

        return $newData;
    }
}
