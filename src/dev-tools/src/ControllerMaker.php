<?php
namespace HyperfAdmin\DevTools;

use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use HyperfAdmin\Admin\Controller\AdminAbstractController;

class ControllerMaker extends AbstractMaker
{
    public function make($model_class, $path, $config)
    {
        $model_name = array_last(explode('\\', $model_class));
        $controller_name = $model_name . 'Controller';
        $class_namespace = $this->pathToNamespace($path);
        $save_path = BASE_PATH . '/' . $path . '/' . $controller_name . '.php';
        /** @var \Nette\PhpGenerator\ClassType $class */
        /** @var PhpNamespace $namespace */
        [
            $namespace,
            $class,
        ] = $this->getBaseClass($save_path, $class_namespace, $controller_name, AdminAbstractController::class);
        $namespace->addUse($model_class);
        $class->addProperty('model_class', new Literal($model_name . '::class'));
        $form = $config['form'] ?? [];
        if(!$form) {
            return false;
        }
        $route = $this->splitToRouteName($model_name);
        $options = $this->optionsMake($config, $route);
        $class->addMethod('scaffoldOptions')->setBody("return " . $this->arrayStr($options) . ";");
        foreach($config['init_hooks'] as $item) {
            $class->addMethod($item)->setParameters($this->hooksParameter($item));
        }
        $code = $this->getNamespaceCode($namespace);
        if(file_put_contents($save_path, $code) === false) {
            return false;
        }

        return $class_namespace . '\\n' . $controller_name;
    }

    public function splitToRouteName($greatHumpStr){
        $arr = preg_split('/(?<=[a-z0-9])(?=[A-Z])/x', $greatHumpStr);
        return strtolower(implode("_", $arr));
    }

    public function hooksParameter($hook_name)
    {
        $map = [
            'beforeInfo' => [
                (new Parameter('info'))->setReference(),
            ],
            'beforeListQuery' => [
                (new Parameter('conditions'))->setReference(),
            ],
            'beforeListResponse' => [
                (new Parameter('list'))->setReference(),
            ],
            'meddleFormRule' => [
                (new Parameter('id')),
                (new Parameter('form_rule'))->setReference(),
            ],
            'beforeFormResponse' => [
                (new Parameter('id')),
                (new Parameter('record'))->setReference(),
            ],
            'beforeSave' => [
                (new Parameter('pk_val')),
                (new Parameter('data'))->setReference(),
            ],
            'afterSave' => [
                (new Parameter('pk_val')),
                (new Parameter('data'))->setReference(),
            ],
            'beforeDelete' => [
                (new Parameter('pk_val')),
            ],
            'afterDelete' => [
                (new Parameter('pk_val')),
                (new Parameter('deleted')),
            ],
        ];

        return $map[$hook_name] ?? [];
    }

    public function optionsMake($data, $route)
    {
        $options = [];
        $yes = [
            'createAble',
            'exportAble',
            'deleteAble',
            'defaultList',
            'filterSyncToQuery',
        ];
        foreach($yes as $item) {
            if(!in_array($item, $data['base_init'])) {
                $options[$item] = false;
            }
        }
        $not = [];
        foreach($not as $item) {
            if(in_array($item, $data['base_init'])) {
                $options[$item] = false;
            }
        }
        $options['form'] = $this->transForm($data['form'] ?? []);
        if(in_array('editButton', $data['base_init'] ?? [])) {
            $options['table']['rowActions'][] = [
                'type' => 'jump',
                'target' => "/{$route}/{id}",
                'text' => '编辑',
            ];
        }
        if(in_array('deleteButton', $data['base_init'] ?? [])) {
            $options['table']['rowActions'][] = [
                'type' => 'api',
                'target' => "/{$route}/delete",
                'text' => '删除',
                'props' => [
                    'type' => 'danger',
                ],
            ];
        }

        return $options;
    }

    public function transForm($form)
    {
        $rules['id|#'] = '';
        $have_option_type = ['select', 'checkbox', 'radio'];
        foreach($form as $item) {
            $key = $item['label'] ? $item['field'] . '|' . $item['label'] : $item['field'];
            $rules[$key] = [];
            if($item['type'] !== 'input') {
                $rules[$key]['type'] = $item['type'];
            }
            if($item['rule']) {
                $rules[$key]['rule'] = implode('|', $item['rule']);
            }
            if($item['props']) {
                $rules[$key]['props'] = $item['props'];
            }
            if($item['default']) {
                $rules[$key]['default'] = $item['default'];
            }
            if($item['info'] ?? '') {
                $rules[$key]['info'] = $item['info'];
            }
            if(count($rules[$key]) == 1 && isset($rules[$key]['rule'])) {
                $rules[$key] = $rules[$key]['rule'];
            }
            if(in_array($item['type'] ?? '', $have_option_type) && $item['options']) {
                $rules[$key]['options'] = $item['options'];
            }
            if($item['field'] == 'id') {
                $rules[$key] = [];
            }
            if($rules[$key] === []) {
                $rules[$key] = '';
            }
        }

        return $rules;
    }
}
