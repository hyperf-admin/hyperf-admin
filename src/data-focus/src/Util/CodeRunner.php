<?php
namespace HyperfAdmin\DataFocus\Util;

use Hyperf\Utils\Str;
use HyperfAdmin\DataFocus\Model\PluginFunction;

class CodeRunner
{
    public $runner;

    public $namespace;

    public function __construct()
    {
        $this->runner = (new PHPSandbox())->setFunctionValidator(function ($name, $sandbox) {
            if(preg_match('/^(df|array|str|url)/', $name)) {
                return true;
            }

            return false;
        });
    }

    public function run($code, $ids = [])
    {
        $ids = (array)$ids;
        $result = [];
        $errors = [];
        $startTime = microtime(true);
        $namespace = sprintf("PHPSandbox_%s_%s", md5($code), (int)(microtime(true) * 1000));
        $this->namespace = $namespace;
        $this->runner->setNamespace($namespace);
        if(preg_match_all('/df_\w+/', $code, $m)) {
            $plugins = PluginFunction::query()
                ->where('status', PluginFunction::STATUS_YES)
                ->whereIn('func_name', array_unique($m[0]))
                ->get()
                ->toArray();
            $plugin_str = [];
            foreach($plugins as $item) {
                $plugin_str[] = sprintf("<?php\n%s\n?>", $item['context']);
            }
            $code = implode("\n", $plugin_str) . $code;
        }
        try {
            if(preg_match_all('/<\?(?:php|=).*?\?>/msui', $code, $match)) {
                foreach($match[0] ?? [] as $part) {
                    $ret = $this->executePHPCode($part);
                    $wrap = null;
                    $replace = is_array($ret) ? json_encode($ret) : $ret;
                    if(Str::contains($code, "\"$part")) {
                        $wrap = '"';
                    }
                    if(Str::contains($code, "'$part")) {
                        $wrap = "'";
                    }
                    if($wrap) {
                        $replace = $wrap === '"' ? str_replace('"', '\"', $replace) : str_replace("'", "\'", $replace);
                    }
                    $code = str_replace($part, $replace, $code);
                }
            }
            if(preg_match_all('/\{{([^}]+)\}}/i', $code, $m)) {
                foreach($m[1] as $each) {
                    $ret = $this->pipFilter($each);
                    $code = Str::replaceArray('{{' . $each . '}}', [$ret], $code);
                }
            }
            $code = removeComment($code);
            $parse = str_get_html($code, true, true, DEFAULT_TARGET_CHARSET, false);
            $children = $parse ? $parse->root->children() : [];
            $node_ids = [];
            foreach($children as $index => $node) {
                $id = $node->getAttribute('id') ?: $index;
                if(in_array((string)$id, $node_ids)) {
                    throw new \Exception('标签id重复: ' . $id);
                }
                $node_ids[] = (string)$id;
                if($ids && !in_array((string)$id, $ids)) {
                    continue;
                }
                $type = $node->tag;
                $text = trim($node->innertext());
                if($node->hasChildNodes()) {
                    throw new \Exception('一级标签不允许嵌套');
                }
                $data = [];
                $part_start_time = microtime(true);
                $part_type = 'table'; // table/info/filter ...
                $table_plugin = $this->explodeTablePlugin($node->getAttribute('table_plugin') ?: '');
                if($type == 'sql') {
                    if(!Str::startsWith($text, ['select', 'SELECT'])) {
                        throw new \Exception('只允许 SELECT 语句');
                    }
                    $count = count(array_filter(explode(';', $text)));
                    if($count > 1) {
                        throw new \Exception('一个 <sql></sql> 节点只允许一个sql语句');
                    }
                    //$explain = $conn->select('explain ' . $text);
                    $data = df_db_query($text, $node->getAttribute('dsn') ?: 'default');
                    if(preg_match_all('/[\w\'\"]+,?\s+--\s+@.*/mui', $text, $m)) {
                        $self = $this;
                        $field_plugin = array_map(function ($item) use ($self) {
                            [
                                $field,
                                $plugin_str,
                            ] = preg_split('/\s+,?--\s+/', $item);

                            return [
                                preg_replace('/[\'\",\s]+/', '', $field),
                                $self->explodeTablePlugin(preg_replace('/@/mui', '', $plugin_str)),
                            ];
                        }, $m[0]);
                        $data = $this->filterByFieldPlugin($data, $field_plugin);
                    }
                }
                if($type == 'json') {
                    $data = df_json_decode($text);
                }
                if($type == 'info') {
                    $part_type = 'info';
                    $data = $text;
                }
                if($type == 'filter') {
                    $part_type = 'filter';
                    $data = $text;
                }
                $data = $this->filterByTablePlugin($data, $table_plugin);
                $dump = df_dump_get();
                if($dump) {
                    $result[] = [
                        'id' => 'debug_dump',
                        'type' => 'debug_dump',
                        'data' => $dump,
                    ];
                }
                $result[] = [
                    'id' => $id,
                    'type' => $part_type,
                    'chart' => (object)$this->getChartOptions($node->getAttribute('chart'), $data),
                    'col' => $this->getColOption($node->getAttribute('span') ?: $node->getAttribute('col')),
                    'show_table' => $node->hasAttribute('show_table') ? $node->getAttribute('show_table') == 'true' : false,
                    'data' => (array)$data,
                    'tips' => $node->getAttribute('tips') ?: '',
                    'runtime' => [
                        'use_ms' => (int)((microtime(true) - $part_start_time) * 1000),
                        'sql' => df_collected('sql_logs'),
                    ],
                ];
                df_collected_clear('sql_logs');
                df_dump_clear();;
            }
        } catch (\Exception $exception) {
            $errors[] = sprintf("Exception:%s", $exception->getMessage());
        } catch (\Throwable $throwable) {
            $errors[] = sprintf("Throwable:%s on line %s", $throwable->getMessage(), $throwable->getLine());
        }
        $endTime = microtime(true);

        return [
            'result' => $result,
            'errors' => array_map(function ($item) use ($namespace) {
                return Str::replaceArray($namespace . '\\', [''], $item);
            }, $errors),
            'info' => [
                'use_ms' => (int)(($endTime - $startTime) * 1000),
            ],
        ];
    }

    // plugin_b:11,33|plugin_a
    public function explodeTablePlugin($str)
    {
        if(!$str) {
            return [];
        }
        $parts = explode('|', $str);

        return array_map(function ($item) {
            $tokens = preg_split('/[:,]/', $item);
            $func_name = array_shift($tokens);

            return [
                $func_name,
                $tokens,
            ];
        }, $parts);
    }

    public function filterByTablePlugin($data, $plugins)
    {
        foreach($plugins as $item) {
            $name = array_shift($item);
            $params = $item[0] ?? [];
            array_unshift($params, $data);
            $function_name = $this->namespace ? "$this->namespace\\$name" : $name;
            $data = call($function_name, $params);
        }

        return $data;
    }

    public function filterByFieldPlugin($data, $plugins)
    {
        foreach($plugins as $item) {
            $field = array_shift($item);
            foreach($item as $field_plugins) {
                $field_values = array_column($data, $field);
                foreach($field_plugins as $plugin) {
                    $name = array_shift($plugin);
                    array_unshift($plugin, $field, $field_values);
                    if(function_exists($name)) {
                        $function_name = $name;
                    } else {
                        $function_name = $this->namespace ? "$this->namespace\\$name" : $name;
                    }
                    $trans = call($function_name, $plugin);
                    foreach($data as &$row) {
                        $row[$field] = $trans[$row[$field]] ?? $row[$field];
                        unset($row);
                    }
                }
            }
        }

        return $data;
    }

    public function executePHPCode($code)
    {
        return $this->runner->execute($code);
    }

    public function getChartOptions($conf, &$data)
    {
        $json = df_json_decode($conf);
        if($json) {
            return $json;
        }
        if($conf == 'NumberPanel') {
            $data = array_values($data);
            if($data) {
                $type = array_intersect([
                    'number',
                    'label',
                ], array_map('strtolower', array_keys($data[0])));
                if(count($type) !== 2) {
                    $new = [];
                    foreach($data[0] as $key => $val) {
                        $new[] = ["number" => $val, 'label' => $key];
                    }
                    $data = $new;
                }
            }

            return [
                'type' => 'NumberPanel',
            ];
        }
        if(Str::startsWith($conf, 'LineChart')) {
            $token = explode('|', $conf);
            array_shift($token);
            $field = explode(',', array_shift($token));
            $x = array_shift($field);
            $y = $field;

            return [
                'type' => 'LineChart',
                'props' => [
                    'xAxis' => $x,
                    'yAxis' => $y ?: null,
                ],
            ];
        }
        if(Str::startsWith($conf, 'PieChart')) {
            $data = array_values($data);
            if($data) {
                $type = array_intersect([
                    'number',
                    'label',
                ], array_map('strtolower', array_keys($data[0])));
                if(count($type) !== 2) {
                    $new = [];
                    foreach($data[0] as $key => $val) {
                        $new[] = ["number" => $val, 'label' => $key];
                    }
                    $data = $new;
                }
            }

            return [
                'type' => 'PieChart',
            ];
        }
        if(Str::startsWith($conf, 'ColumnChart')) {
            $token = explode('|', $conf);
            array_shift($token);
            $field = explode(',', array_shift($token));
            $x = array_shift($field);
            $y = $field;

            return [
                'type' => 'ColumnChart',
                'props' => [
                    'xAxis' => $x,
                    'yAxis' => $y ?: null,
                ],
            ];
        }

        return df_json_decode($conf);
    }

    public function getColOption($conf)
    {
        if(!$conf) {
            return ['span' => 24];
        }
        $decode = df_json_decode($conf);
        if(is_array($decode)) {
            return $decode;
        }

        return ['span' => (int)($conf ?: 24)];
    }

    public function pipFilter($str)
    {
        $parts = explode('|', trim($str));
        $first = array_shift($parts);
        $code = "<?php\n\$ret = $first;";
        $raw = false;
        foreach($parts as $each) {
            if($each === 'raw') {
                $raw = true;
                continue;
            }
            $code .= "\n\$ret = $each(\$ret);";
        }
        $code .= "\nreturn \$ret;?>";
        $ret = $this->executePHPCode($code);

        return $raw ? $ret : "\"$ret\"";
    }
}
