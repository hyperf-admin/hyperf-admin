<?php
declare(strict_types=1);
namespace HyperfAdmin\DataFocus\Controller;

use HyperfAdmin\BaseUtils\Scaffold\Controller\AbstractController;
use HyperfAdmin\DataFocus\Model\PluginFunction;
use HyperfAdmin\DataFocus\Util\PHPSandbox;

class PluginFunctionController extends AbstractController
{
    public $model_class = PluginFunction::class;

    public function scaffoldOptions()
    {
        return [
            'form' => [
                'id|#' => '',
                'name|中文名称' => [
                    'rule' => 'required|max:50',
                ],
                'type|类型' => [
                    'type' => 'select',
                    'rule' => 'required',
                    'options' => PluginFunction::$type,
                    'default' => PluginFunction::TYPE_COLUMN,
                ],
                'context|方法体' => [
                    'type' => 'code',
                    'rule' => 'required',
                ],
                'status|状态' => [
                    'type' => 'select',
                    'rule' => 'required',
                    'options' => PluginFunction::$status,
                    'default' => PluginFunction::STATUS_YES,
                ],
            ],
            'table' => [
                'columns' => [
                    'id',
                    'name',
                    [
                        'field' => 'func_name',
                        'title' => '方法名',
                    ],
                    [
                        'field' => 'status',
                        'enum' => [
                            0 => 'info',
                            1 => 'success',
                        ],
                    ],
                ],
                'rowActions' => [
                    [
                        'type' => 'jump',
                        'target' => '/plugin_function/{id}',
                        'text' => '编辑',
                    ],
                ],
            ],
        ];
    }

    public function beforeSave($id, &$data)
    {
        $sandbox = make(PHPSandbox::class)->setFunctionValidator(function ($name, $sandbox) {
            if(preg_match('/^(df|array|str|url)/', $name)) {
                return true;
            }

            return false;
        });
        $parse = $sandbox->validateCode(sprintf("<?php %s", $data['context']));
        if(count($parse) !== 1) {
            $this->exception('只允许定义一个方法');
        }
        /** @var \PhpParser\Node\Stmt\Function_ $func */
        $func = $parse[0];
        $data['func_name'] = $func->name->toString();
        if(count($func->params) < 1) {
            $this->exception('方法至少需要一个参数');
        }
    }
}
