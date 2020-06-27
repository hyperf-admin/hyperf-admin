## 路由注册

一个独立的业务模块需要在`config/routes/`下添加业务的路由文件，在该文件内完成业务模块所有的路由定义。可以使用`register_route`方法来定义您的路由。

```php
<?php
use Hyperf\HttpServer\Router\Router;
use App\Controller\IndexController;

register_route('/index', IndexController::class, function ($controller) {
    // 其他路由的定义
    Router::get('/hello-hyperf', [$controller, 'hello']);
});
```

!> 如果完全是自定义的前端页面，建议不使用`register_route`注册路由，`register_route`内部会注册一些脚手架路由

**脚手架路由**

| uri                                                          | 请求方式 | 控制器方法        | 说明                                            |
| :----------------------------------------------------------- | :------- | :---------------- | :---------------------------------------------- |
| `path`/list.json<br>`path`/info                              | GET      | info              | 下发列表页的配置                                |
| `path`/form.json<br>`path`/form<br>`path`/{id:\d+}.json<br>`path`/{id:\d+} | GET      | form、edit        | 下发表单配置                                    |
| `path`/list                                                  | GET      | list              | 下发列表数据                                    |
| `path`/form                                                  | POST     | save              | 新增时数据保存接口                              |
| `path`/{id:\d+}                                              | POST     | save              | 编辑时数据保存接口                              |
| `path`/delete                                                | POST     | delete            | 删除接口                                        |
| `path`/batchdel                                              | POST     | batchDelete       | 批量删除接口                                    |
| `path`/rowchange/{id:\d+}                                    | POST     | rowChange         | 行编辑数据保存接口                              |
| `path`/childs/{id:\d+}                                       | GET      | getTreeNodeChilds | 树结构的列表页动态获取子节点的接口              |
| `path`/newversion/{id:\d+}/{last_ver_id:\d+}                 | GET      | newVersion        | 表单编辑时或数据对象的版本信息接口              |
| `path`/export                                                | POST     | export            | 导出任务接口                                    |
| `path`/act                                                   | GET      | act               | 可用于当前model提供select组件远程搜索的数据接口 |
| `path`/import                                                | POST     | import            | 导入接口                                        |

## 脚手架概览

在编写控制器时需`继承`脚手架的抽象类`AbstractController`，并在`scaffoldOptions`方法中定义页面的配置。

```php
<?php
declare(strict_types=1);
namespace App\Controller;

use Hyperf\Admin\Controller\AdminAbstractController;

class IndexController extends AdminAbstractController
{
  	// 操作的 model 对象
  	public $model_class = User::class;
  
  	// 操作的entity
  	// entity 为脚手架抽象出的一个实体, 包含对象的 curd 操作
  	// 目前支持 mysql/es/mongo/api
  	// model 和 entity 任选其一即可
  	public $entity_class = UserEntity::class;
  	
  	// 脚手架核心配置
    public function scaffoldOptions()
    {
        return [
            // 自定义创建按钮的跳转路由, 默认 /user/form
            'form_path' => '/custom/path',
            // 是否允许创建, 默认 true, false怎隐藏页面列表上方的新建按钮
            'createAble' => false,
            // 是否允许删除, 默认 true
            'deleteAble' => true,
            // 是否开启通知查询功能，开启后在页面路由发生变化时，会根据当前页面参数查询页面有没有通知消息
            'noticeAble' => true,
            // 是否需要分页器 默认true
            'paginationEnable' => true,
            // 是否显示导出按钮, 默认true
            'exportAble' => true,
            // 列表页是否默认执行查询, 默认执行查询
            'defaultList' => false,
            // 搜索条件, 前端页面会根据此处配置渲染搜索条件，可以像表单一样配置规则
            // 搜索条件中支持模糊搜索也很简单 %field_name%, field_name%, %field_name, 如此定义字段即可
            'filter' => ['id', 'username%', 'create_at'],
            // 列表的基础筛选条件, 列表的查询均会携带上此处的条件, 详情请查看where2query方法
            'where' => [
                'type' => User::STATUS_ON,
            ],
            // 筛选条件是否同步到地址栏
            "filterSyncToQuery" => false,
            // 列表的排序
            'order_by' => 'id desc',
            // 表单页面的UI配置, 详参 http://form-create.com/v2/iview/global.html
            'formUI' => [
                'form' => [
                    'lableWidth' => '300px'
                ],
                'submitBtn' => [
                    'innerText' => '这是提交按钮'
                ]
            ],
            // form表单的定义, 核心配置, 不可或缺，请求请查看表单页配置
            'form' => [
                'field|字段名称' => [
                    // 字段验证规则
                    'rule' => 'required|max',
                    'type' => 'input',
                    'info' => '字段备注',
                ],
            ],
          	// 页面提示
            'notices' => [
            		[
                  	'type' => 'warning',
                    'message' => '提示信息',
                    'actionsPlacement' => 'right',
                    'closable' => true,
                    'actions' => [
                        [
                            'props' => [
                                'size' => 'mini',
                                'type' => 'success',
                            ],
                            'text' => '点我更新',
                            'type' => 'native',
                        ]
                    ],
                  	'when' => function($filters) { return true;}
                ]
            ],
            // 第三方数据补充, 子项定义规范, 详见下方列表第三方数据补充部分
            'hasOne' => [
                'mt_oms.mt_oms.user_role:user_id,role_id'
            ],
            // 列表定义
            'table' => [
                // tabs 列表页分页签
                'tabs' => [],
                // 定义渲染列表, 未定义则获取 form 中所有
                'columns' => [],
                // 订单行操作按钮
                'rowActions' => [],
                // 列表上方批量操作的按钮
                'batchButtons' => [],
                // 页面上方操作按钮
                'topActions' => [],
            ],
        ];
    }
}
```


## 内置钩子

```php
    // 列表页下发配置接口的前置钩子
    public function beforeInfo(&$info) {}
  
    // 列表页执行搜索前的钩子, 可用于修改 where 条件
    public function beforeListQuery(&$conditions) {}
  
    // 列表数据响应前的钩子, 可用于补充额外数据
    public function beforeListResponse(&$list) {}
  
    // form 规则下发前的干预钩子
    public function meddleFormRule($id, &$form_rule) {}
  
    // form 响应前的钩子
    public function beforeFormResponse($id, &$record) {}
  
    // 表单保存前端钩子函数
    public function beforeSave($pk_val, &$data) {}
  
    // 表单保存后的钩子函数
    public function afterSave($pk_val, &$data) {}
  
    // 删除前的回调钩子
    public function beforeDelete($pk_val) {}
  
    // 删除后的回调钩子
    public function afterDelete($pk_val, $deleted) {}
```
## 表单定义

```php
public function scaffoldOptions()
{
    return [
        ....
        // 表单配置
        'form' => [
            // 字段验证规则 
            // 请参考 https://hyperf.wiki/#/zh-cn/validation?id=%e9%aa%8c%e8%af%81%e8%a7%84%e5%88%99
            'rule' => 'required|max:10',
            // 请参考 http://www.form-create.com/v2/element-ui/components/input.html
            'type' => 'input',
            // 表单默认值
            'default' => '',
            'info' => '字段备注',
            // 只读属性，当编辑时有效
            'readonly' => true,
            // 表单选项，只有支持options选项的组件设置才有效，可以定义一个callback方法，可以参考formOptionsConvert方法
            'options' => [],
            // 其他组件属性，请参考具体组件的props的定义
            'props' => [],
            // 定义依赖项
            'depend' => [
                'field' => 'target_type',
                'value' => [],
            ],
            // col 布局规则 http://www.form-create.com/v2/element-ui/col.html
            'col' => [
                // 表单长度
                'span' => 12,
                // 标签宽度
                'labelWidth' => 150,
            ],
            // 动态修改其他字段规则 详见下方联动小节
            'compute' => [
                "will_set_field" => [
                    "when" => ['=', 1],
                    "set" => [
                      //
                    ]
                ]
            ],
            // 该字段规则回调方法，可以用于重置字段规则
            'render' => function () {
            },
            // 开启input框的复制功能
            'copy_show' => true,
        ],
    ];
}
```

## 列表定义

```php
public function scaffoldOptions()
{
    return [
        ....
      	 // 非必须项, 没有定义则从form转义
        'columns' => [
            '字段名', // 简写模式, 直接从form配置转义
            [
                'field' => 'mall_name',
                'title' => '店铺',
                // 字段渲染规则，默认为空
                'type' => '',
                // 是否虚拟字段，虚拟字段在查询脚手架model时，会忽略该字段
                'virtual_field' => true,
                // 表头说明
                'info' => '括号内为商家承担',
                // 定义该字段显示在哪些tab选项中
                'depend' => [
                    'tab' => [(string)Coupon::TYPE_MALL_MONEY_OFF],
                ],
                // 按字段升降查询功能
                'sortable' => true,
                // 是否允许编辑，调用*/rowchange/:id接口
                'edit' => true,
                // 枚举值，可以options中的数据转换成Tag显示效果，https://element.eleme.cn/#/zh-CN/component/tag
                'options' => [],
                'enum' => [ // tag 的 type 类型, 参见 element 标签
                    0 => 'info',
                    1 => 'success',
                ],
              	// 单独处理某个字段
              	'render' => function($val, $row) { return $val;}
            ],
        ],
    ];
}
```

## 按钮

`rowActions`, `topButtons`, `topActions`, `notices.*.actions` 的节点定义

1.  页面跳转

    ```php
    [
      	'type' => 'jump',
      	'target' => '/crontab/{id}', // 本地路由或三方地址
      	'text' => '编辑',
      	'props' => [] // element el-button 的属性
    ]
    ```

2.  请求后端api

    ```php
    [
        'type' => 'api',
        'target' => '/resource/delete', // 支持变量替换
        'text' => '删除',
      	'method' => 'POST', // 默认POST
       	'props' => [
          	'type' => 'danger',
        ],
      	// 当前按钮可以定义依赖条件, 动态显示
        'when' => [
            ['gid', '=', Resource::RESOURCE_ROOT_ID]
        ]
    ],
    ```

3.  model弹窗表单

    ```php
    // 直接定义表单规则 rules
    [
        'action' => 'module',
        'target' => '/user/test/{id}',
        'text' => '弹窗',
      	// rules 的定义同 form rule
        'rules' => [ 
            'file|视频' => [
                'type' => 'file',
            ],
        ],
    ]
    
    // 调用其他Controller form表单
    [
        'action' => 'module',
      	 // 若没有rules节点则自定调用 target 接口拉取表单配置
        'target' => '/user/form',
        'text' => '弹窗',
    ]
    
    // 调用其他 Controller 的列表
    [
        'type' => 'table',
        'target' => '',
        'props' => [
          'listApi' => '/role/list?id={id}',
          'infoApi' => '/role/info',
          'options' => [
            'showFilter' => false,
            'createAble' => false
          ]
        ],
        'text' => '**记录',
    ]
    ```
    
    按钮较多时均可调整为按钮组
    
    ```php
    [
      [
        $action_conf1,
        $action_conf2
      ]
    ]
    ```
    
    

## 关联数据

```php
public function scaffoldOptions()
{
    return [
        ....
        // 一对一关系
        'hasOne' => [
            // 此处定义了补充的第三方数据是什么, 从哪里取
            // [pool.]db.table:[local_key->]foreign_key,other_key
            'hyperf_admin.hyperf_admin.user_role:id->user_id,role_id', // 完整定义
            'hyperf_admin.user_role:id->user_id,role_id', // 缺省 pool
            'hyperf_admin.user_role:user_id,role_id', // 缺省 pool,local_key
            'hyperf_admin.user_role:user_id,role_id as rid', // 补充字段使用别名, 避免覆盖list中同名字段
        ],
        // 一对多或多对多关系
        'hasMany' => [
            'hyperf_admin.hyperf_admin.operator_log:id->user_id,username'
        ]
    ];
}
```

`[pool.]db.table:[local_key->]foreign_key,other_key`

分别对应`连接池`(非必须, 默认dfault), `库名`, `表名`, `本地关联字段`(非必须, 默认id), `逻辑外键`, `其他要补充的字段`, 若系统为查询到第三方数据, 相应的补充字段将初始为 `null`

## 页面提示

```php
public function scaffoldOptions()
{
    return [
        ....
        // 页面提示信息
        'notices' => [
            [
               'type' => 'warning',
               'message' => '提示信息',
               'actionsPlacement' => 'right',
               'closable' => true,
               'actions' => [], // 按钮
               'when' => function($filters) { return true;}
            ]
        ]
    ];
}
```
