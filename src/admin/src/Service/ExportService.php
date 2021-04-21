<?php
namespace HyperfAdmin\Admin\Service;

use Carbon\Carbon;
use Hyperf\Utils\Str;
use HyperfAdmin\Admin\Model\ExportTasks;
use HyperfAdmin\BaseUtils\Guzzle;
use HyperfAdmin\BaseUtils\Log;

class ExportService
{
    const LIST_API_SUFFIX = '/list';

    const INFO_API_SUFFIX = '/info';

    /**
     * @param int   $status
     * @param int   $operator_id
     * @param array $columns
     * @param array $filter_options ['not_like' => ['list_api' => ['%123%', '%345'], 'filters' => ['%123%', '%456%']],'like' => ['list_pai' => ['%ttt%']], 'in' => ['operator_id' => [123,123,123]], 'not_in' => ['operator_id' => [456,5466,234]]]
     *
     * @return array
     */
    public function getTasks($status = 0, $operator_id = 0, $columns = ['*'], $filter_options = [])
    {
        $query = ExportTasks::query()->select($columns);
        if($status !== null) {
            $query->where('status', $status);
        }
        if($operator_id) {
            $query->where('operator_id', $operator_id);
        }
        if(!empty($filter_options['not_like'])) {
            $query->where(function ($q) use ($filter_options) {
                foreach($filter_options['not_like'] as $column => $likes) {
                    foreach($likes as $like) {
                        $q->where($column, 'not like', $like);
                    }
                }
            });
        }
        if(!empty($filter_options['like'])) {
            $query->where(function ($q) use ($filter_options) {
                foreach($filter_options['like'] as $column => $likes) {
                    foreach($likes as $like) {
                        $q->where($column, 'like', $like);
                    }
                }
            });
        }
        if(!empty($filter_options['in'])) {
            $query->where(function ($q) use ($filter_options) {
                foreach($filter_options['in'] as $column => $ins) {
                    $q->whereIn($column, $ins);
                }
            });
        }
        if(!empty($filter_options['not_in'])) {
            $query->where(function ($q) use ($filter_options) {
                foreach($filter_options['not_in'] as $column => $ins) {
                    $q->whereNotIn($column, $ins);
                }
            });
        }
        $query->orderBy('id', 'desc');

        return $query->get() ?: [];
    }

    public function processTask(ExportTasks $task)
    {
        try {
            if(in_array(ExportTasks::find($task->id)->status, [
                ExportTasks::STATUS_PROCESSING,
                ExportTasks::STATUS_SUCCESS,
            ])) {
                Log::get('export_service')->info('正在处理该任务，不要重复处理', [$task]);

                return;
            }
            $task->fill(['status' => ExportTasks::STATUS_PROCESSING])->save();
            $list_api = 'http://127.0.0.1:' . config('server.servers.0.port') . $task->list_api;
            $query['_page'] = ($query['_page'] ?? 1);
            $size = 100;
            $query['_size'] = $size;
            $query = array_merge($query, $task->filters);
            $headers = [
                'X-Real-IP' => '127.0.0.1',
            ];
            $total = 999;
            if(Str::endsWith($task->list_api, self::LIST_API_SUFFIX)) {
                $info_api = 'http://127.0.0.1:' . config('server.servers.0.port') . str_replace(self::LIST_API_SUFFIX, self::INFO_API_SUFFIX, $task->list_api);
            } else {
                $subject = explode('/', $task->list_api)[1];
                $info_api = 'http://127.0.0.1:' . config('server.servers.0.port') . '/' . $subject . '/info';
            }
            $info = Guzzle::get($info_api, [], $headers);
            $table_headers = array_filter($info['payload']['tableHeader'], function ($item) {
                return $item['hidden'] ?? true;
            });
            $table_headers_str = [];
            foreach($table_headers as $item) {
                $table_headers_str[] = $item['title'];
            }
            $table_headers_str = implode(',', $table_headers_str);
            $file_name = '《' . $task->name . '》下载-' . Carbon::now()->format('YmdHis') . '.csv';
            $file_path = '/tmp/' . $file_name;
            file_put_contents($file_path, $this->encoding($table_headers_str) . PHP_EOL);
            $counter = 0;
            $parse = parse_url($list_api);
            if(isset($parse['query'])) {
                parse_str($parse['query'], $_query);
                $query = array_merge($query, $_query);
            }
            while(($query['_page'] - 1) * $size < $total) { // offset值
                $ret = Guzzle::get($list_api, $query, $headers);
                $total = $ret['payload']['total'] <= ExportTasks::LIMIT_SIZE_MAX ? $ret['payload']['total'] : ExportTasks::LIMIT_SIZE_MAX;
                if(!is_array($ret['payload']['list'])) {
                    throw new \Exception('列表获取异常，任务id：' . $task->id);
                }
                foreach($ret['payload']['list'] as $item) {
                    $row = $this->getRow($table_headers, $item);
                    $counter++;
                    file_put_contents($file_path, $this->encoding($row) . PHP_EOL, FILE_APPEND);
                }
                $task->fill([
                    'total_pages' => ceil($total / $size),
                    'current_page' => $query['_page'],
                ])->save();
                $query['_page'] += 1;
            }
            $bucket = config('file.export_storage', config('file.default'));
            $info = move_local_file_to_filesystem($file_path, '1/export_task/' . $file_name, true, $bucket);
            if($info) {
                if (config('file.default') === 'local') {
                    @chmod(config('file.storage.local.root') . '/' . $info['path'], 0644);
                }
                
                $task->fill([
                    'status' => ExportTasks::STATUS_SUCCESS,
                    'download_url' => $info['path'],
                ])->save();
                Log::get('export_service')
                    ->info(sprintf('export task success, file_name:%s id:%s rows:%s', $info['file_path'], $task->id, $counter), [], 'export_task');
            } else {
                Log::get('export_service')
                    ->error(sprintf('export task fail id:%s', $task->id), [], 'export_task');
                $task->fill(['status' => ExportTasks::STATUS_FAIL])->save();
            }
        } catch (\Exception $exception) {
            Log::get('export_service')
                ->error(sprintf('export task fail id:%s', $task->id), ['exception' => $exception], 'export_task');
            $task->fill(['status' => ExportTasks::STATUS_FAIL])->save();
        }
    }

    public function getRow($table_headers, $data)
    {
        $arr = [];
        foreach($table_headers as $item) {
            if(isset($item['options'])) {
                $arr[] = $item['options'][$data[$item['field']]] ?? ($data[$item['field']] ?? '');
                continue;
            }
            if(isset($item['enum'])) {
                $arr[] = $item['enum'][$data[$item['field']]] ?? '';
                continue;
            }
            $arr[] = $data[$item['field']] ?? '';
        }
        $arr = array_map(function ($item) {
            $item = csv_big_num($item);
            $item = preg_replace('/\\n/', ' ', $item);

            return $item;
        }, $arr);

        return implode(',', array_map(function ($item) {
            if(is_array($item)) {
                return json_encode($item, JSON_UNESCAPED_UNICODE);
            }

            return $item;
        }, $arr));
    }

    public function encoding($str)
    {
        //return iconv('utf-8', 'gbk//ignore', $str);
        return mb_convert_encoding($str, "GBK", "UTF-8");
    }

    public function getFirstSameTask($url, $filters, $operator_id)
    {
        return ExportTasks::where('filters', json_encode($filters))
            ->where('list_api', $url)
            ->where('operator_id', $operator_id)
            ->where('created_at', '>=', Carbon::today()->toDateTimeString())
            ->where('status', '!=', ExportTasks::STATUS_SUCCESS)
            ->first();
    }
}
