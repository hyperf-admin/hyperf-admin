支持 列操作按钮, 批量操作按钮, 列表页顶部按钮

### 基础属性

```php
[
    "text" => "", //按钮文案
    "type" => "jump", // 按钮类型 默认 jump, 可选 form, api
    "target" => "", // 动作目标 本地路由, 网址, 后端api
    "props" => [ // 按钮属性, 更多可见 https://element.eleme.io/#/zh-CN/component/button
        "icon" => "", // 按钮图标 默认无
            "circle" => false, // 圆角 默认false
            "size" => "small", // 默认 small, 可选 medium / small / mini
            "type" => "info", //默认text, primary / success / warning / danger / info / text
    ],
    "when" => [ // 当前按钮的显示条件, 默认无
      ["field_1", "=", 1] // filter过滤的比对数据为当前行 或者 当前页面基础数据
    ]
]

// 除以上基础属性外, 根据按钮类型, 也会有其他额外属性, 具体见下面
```

#### 普通跳转按钮

```javascript
[
    "text" => "编辑",
    "target" => "/user/12"
]
// or
[
    "text" => "文档",
    "target" => "http://hyperf.wiki"
]
```

#### 动作按钮(请求后端API)

点击按钮后, 提示二次确认, 确认后请求后端api

```javascript
[
    "text" => "删除",
    "target" => "/user/12",
    "method" => "POST", // 请求api的方式, 默认POST
]
```

#### 表单型按钮

点击按钮后将以弹窗形式渲染指定表单, 然后搜集后端数据请求到指定api

```javascript
[
    "text" => "审核通过",
    "target" => "/user/12",
    "method" => "POST", // 请求api的方式, 默认POST
    "rules" => [ // 表单的rule规则具体参见表单部分
        "reason|原因" => [
            "rule" => "required"
        ]
    ]
]

// 有联动时

[
    "text" => "审核通过",
    "target" => "/user/12",
    "method" => "POST", // 请求api的方式, 默认POST
    "rules" => [ 
      // 待补充
    ]
]

// or

[
    "text" => "审核通过",
    "target" => "/user/form", // get 方式拉取表单配置, post 方式保存数据
    "method" => "POST", // 请求api的方式, 默认POST
]
```

#### 列表型按钮

点击按钮后将以弹窗的形式渲染指定列表

```php
[
  'type' => 'table', // 调用 src/components/Scaffold/tablist.vue 渲染
  'target' => '', // target 留空
  'props' => [
    'listApi' => '/merchantlog/list?merchant_id={id}', // 列表数据拉取接口
    'infoApi' => '/merchantlog/info', // 列表 配置拉取接口
    'options' => [ // 表单的配置项
      'showFilter' => false,
      'createAble' => false
    ]
  ],
  'text' => '招商记录',
]
```

抽屉型按钮

点击按钮后将打开抽屉, 抽屉内部指定动态调用指定组件

```php
[
  'type' => 'drawer',
  'target' => '', // target 留空
  'text' => '查看日志',
  'props' => [ 
    'component' => 'SocketList', // 需动态调用的组件 src/components/Common 下
    'componentProps' => [ // 组件的 props
      'url' => env('OMS_WEBSOCKET_URL') . '/cronlog?name={name}'
    ],
    // drawer** 为抽屉属性的定义
    // 详见 https://element.eleme.io/#/zh-CN/component/drawer
    'drawerWithHeader' => false, 
    'drawerSize' => '80%',
    'drawerTitle' => '{title}日志',
    'drawerDirection' => 'ttb'
  ]
]
```

### SuperButtonGroup 下拉按钮

上面 SuperButton 的结构改为数组形式即可