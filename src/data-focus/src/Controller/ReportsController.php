<?php
declare(strict_types=1);
namespace HyperfAdmin\DataFocus\Controller;

use HyperfAdmin\Admin\Controller\AdminAbstractController;
use HyperfAdmin\BaseUtils\Constants\ErrorCode;
use HyperfAdmin\DataFocus\Model\ReportChangeLog;
use HyperfAdmin\DataFocus\Model\Reports;
use HyperfAdmin\DataFocus\Util\CodeRunner;

class ReportsController extends AdminAbstractController
{
    public $model_class = Reports::class;

    public function scaffoldOptions()
    {
        return [
            'form' => [
                'id|#' => '',
                'name|名称' => [
                    'rule' => 'required|max:50',
                ],
                'pid|父id' => '',
                'dev_content|开发中的报表内容' => [
                    'type' => 'code',
                    'props' => [
                        'options' => [
                            'language' => 'php',
                        ],
                    ],
                ],
                'bind_rold_ids|授权的角色id' => '',
                'bind_uids|绑定的用户id' => '',
                'create_uid|创建者id' => [
                    'form' => false,
                ],
                'dev_uid|开发者id' => [
                    'form' => false,
                ],
                'crontab|定时任务' => '',
                'config|配置' => [
                    'type' => 'json',
                ],
                'publish_at|最后一次发布时间' => [
                    'type' => 'datetime',
                    'form' => false,
                ],
            ],
            'table' => [
                'columns' => [
                    'id',
                    'name',
                    'crontab',
                    [
                        'field' => 'publish_at',
                        'title' => '最后发布时间',
                    ],
                ],
                'rowActions' => [
                    [
                        'type' => 'jump',
                        'target' => '/reports/panel/{id}?dev=true',
                        'text' => '查看',
                    ],
                    [
                        [
                            'type' => 'jump',
                            'target' => '/reports/{id}',
                            'text' => '编辑',
                        ],
                        [
                            'type' => 'api',
                            'target' => '/reports/publish/{id}',
                            'text' => '发布',
                        ],
                    ],
                    //[
                    //    'type' => 'api',
                    //    'target' => '/reports/{id}',
                    //    'text' => '删除',
                    //    'props' => [
                    //        'type' => 'danger',
                    //    ],
                    //],
                ],
            ],
        ];
    }

    public function beforeSave($id, &$data)
    {
        $codeRunner = make(CodeRunner::class);
        $check = $codeRunner->run($data['dev_content']);
        if ($check['errors']) {
            $this->exception(implode("\n", $check['errors']));
        }
        if (!$id) {
            $data['create_uid'] = $this->userId();
        }
        $data['pid'] = (int)$data['pid'];
        $data['dev_uid'] = $this->userId();
    }

    public function afterSave($id, $data)
    {
        make(ReportChangeLog::class)->fill([
            'report_id' => $id,
            'dev_uid' => $this->userId(),
            'dev_content' => $data['dev_content'],
            'published' => ReportChangeLog::STATUS_NOT,
        ])->save();
    }

    public function execute($id)
    {
        $part_id = $this->request->input('id');
        $dev_mode = (bool)$this->request->input('dev');
        $record = $this->getModel()->find($id)->toArray();
        $code = $dev_mode ? $record['dev_content'] : $record['publish_content'];
        if (!$code) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM, '尚未发布');
        }
        $codeRunner = make(CodeRunner::class);
        $ret = $codeRunner->run($code, $part_id);

        return $this->success($ret);
    }

    public function publish($id)
    {
        $entity = $this->getModel()->find($id);
        $record = $entity->toArray();
        if (!$record) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM, 'Not Found!');
        }
        $code = $record['dev_content'];
        $codeRunner = make(CodeRunner::class);
        $ret = $codeRunner->run($code);
        if ($ret['errors']) {
            return $this->fail(ErrorCode::CODE_ERR_PARAM, implode("\n", $ret['errors']));
        }
        $entity->update([
            'publish_content' => $record['dev_content'],
            'publish_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success();
    }
}
