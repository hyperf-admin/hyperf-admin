<?php
namespace HyperfAdmin\Admin\Service;

use Carbon\Carbon;
use HyperfAdmin\Admin\Model\OperatorLog;
use HyperfAdmin\Admin\Model\Version;
use HyperfAdmin\BaseUtils\Log;
use HyperfAdmin\BaseUtils\Model\BaseModel;

class OperatorLogService
{
    /**
     * 记录日志
     * 调用示例：log_operator($model, $pk_val ? '编辑':'新增', $pk_val, '备注一下');
     * 为保证版本数据完整性，建议在保存完成之后执行此操作
     *
     * @param        $action  string （新增|编辑|删除|导入|导出）
     * @param        $ids     mixed 可以是id或数组
     * @param        $model   mixed string/object/null 模型类，此处模型需要自定义传入，考虑到保存的模型未必是控制器的getModel，也可能包括其他模型的保存，或根本不需要模型
     * @param string $remark  备注内容, default ''
     * @param array  $options 其他选项, default []
     * @param int    $user_id
     *
     * @return mixed
     */
    public static function write($model, $action, $ids, $remark = '', $options = [], $user_id = 0)
    {
        if(!is_array($ids)) {
            $ids = [$ids];
        }
        try {
            // 页面url和名称
            $page_url = request()->header('page-url');
            $parse_url = parse_url($page_url);
            $fragment = $parse_url['fragment'] ?? '/'; // 抽出#后面的部分
            $fragments = explode('?', $fragment); // 去掉querystring
            $page_url = array_shift($fragments);
            $page_name = urldecode(request()->header('page-name', '')); // 页面名称
            $relation_ids = json_encode($ids, JSON_UNESCAPED_UNICODE); // 如果没有版本启用，则只记录操作的id
            // 关联id-版本id记录
            if(is_string($model) && $model) {
                $model = make($model);
            }
            if($model
               && $model instanceof BaseModel
               && method_exists($model, 'isVersionEnable')
               && $model->isVersionEnable()) { // 如果有版本，则记录版本id
                $table = strpos($model->getTable(), '.') ? $model->getTable() : $model->getConnectionName() . '.' . $model->getTable();
                $relation_ids = Version::whereIn('pk', $ids)
                    ->where('table', $table)
                    ->selectRaw('concat_ws("-", pk, max(id)) as relation_ids, pk') // 最大版本id为当前版本id
                    ->groupBy('pk')
                    ->orderBy('pk', 'desc')
                    ->get()
                    ->pluck('relation_ids')
                    ->toArray();
                $relation_ids = json_encode($relation_ids, JSON_UNESCAPED_UNICODE);
            }
            // 其他记录
            $detail_json = [];
            if($remark) {
                $detail_json['remark'] = $remark;
            }
            if($options) {
                $detail_json += $options;
            }
            // 用户信息
            $user_info = auth()->user() ?: (new UserService())->getUser($user_id);
            $client_ip = request()->header('x-real-ip') ?? (request()->getServerParams()['remote_addr'] ?? '');
            $save_data = [
                'page_url' => $page_url,
                'page_name' => $page_name,
                'action' => $action,
                'relation_ids' => $relation_ids,
                'client_ip' => $client_ip,
                'operator_id' => $user_info['id'] ?? 0,
                'nickname' => $user_info['realname'] ?? ($user_info['username'] ?? ''),
            ];
            // {谁}于{时间}在{页面名称}{操作：新增|编辑|删除|导入|导出}了ID为{支持多个}的记录
            $now_date = Carbon::now()->toDateTimeString();
            $detail_json['description'] = "{$save_data['nickname']}于{$now_date} 在{$save_data['page_name']}页{$save_data['action']}了ID为" . implode('、', $ids) . '的记录';
            $detail_json = json_encode($detail_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $save_data['detail_json'] = $detail_json;

            return (bool)OperatorLog::create($save_data);
        } catch (\Exception $e) { // 如果发生错误，为避免中断主程，catch后记录错误信息即可
            Log::get('operator_log')->error('记录通用操作日志发生错误：' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());

            return false;
        }
    }
}
