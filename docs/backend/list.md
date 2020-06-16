![](http://km.innotechx.com/download/attachments/82253382/image2020-5-8_17-43-53.png?version=1&modificationDate=1588931034077&api=v2)

```php
return [
    ......
    // 列表定义
    'table' => [
        // 树型结构列表，默认false
        "is_tree" => true
        // tree 节点为非必须, 默认pid的名称为pid, 不同时需要重写
        "tree" => [
          "pid" => "pid"
        ],
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
```

## 列定义

在`columns`中定义列表中显示的字段与表头，具体配置如下：

```php
'columns' => [ // 非必须项, 无则从form转义
    '字段', // 简写模式, 直接从form配置转义
    // or
    [
        'field' => 'mall_name',
        'title' => '店铺',
        // 是否显示该字段，默认false
        'hidden' => true,
        // 字段渲染规则，默认为空, 详见列渲染
        'type' => '',
        // 是否虚拟字段，虚拟字段在查询脚手架model时，会忽略该字段
        'virtual_field' => true,
        // 设置Popover提示信息，其中
        'popover' => [
            'messages' => [
                '原因：{remark}',
            ],
            'when' => [
                ['status', '=', Activity::STATUS_OFF]
            ]
        ],
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
        // 允许编辑的条件
        'when' => [
            ['status', '=', Activity::STATUS_OFF]
        ],
        // 枚举值，可以options中的数据转换成Tag显示效果，https://element.eleme.cn/#/zh-CN/component/tag
        'options' => [],
        'enum' => [ // tag 的 type 类型, 参见 element 标签
            0 => 'info',
            1 => 'success',
        ],
        // 列宽设置，默认为均分模式，不支持百分比
        'width': '100px',
    ],
],
```

## when用法

### 使用说明

```php
'when' => ["field_1", ">", 1],
// or 多个条件时为"与"判断
'when' => [
    ["field_1", ">", 1],
    ["field_2", "=", 1]
]
```

::: warning 注意 
操作符支持`=`、`>`、`>=`、`<`、`<=`、`!=`、`in`、`not_in`，多个条件时为"与"判断，且注意参数的数据类型是否一致。
:::

- 可以控制行操作及批量按钮是否显示；
- 可以控制批量操作的过滤掉不满足条件的行；

```
'batchButtons' => [
    [
        .....
        // 控制根据条件显示，这里的条件字段来源为queryString
        'when'=> [
            [字段, 操作符, 值]
        ],
        // 为区别控制显示的when关键字，这里使用`selectFilter`
        'selectFilter' => [
            [字段, 操作符, 值]
        ]
    ]
]   
```

- 控制列表行的某一列的编辑状态
- 控制列渲染的popover模式下的启用状态

### 查看源码

```javascript
export function whereFilter(obj, where, fakeKey) {
  if (!where) {
    return true
  }
  let ret = true
  let real_where = where
  if (where[0] && typeof where[0] === 'string') {
    real_where = [where]
  }
  for (let i = 0; i < real_where.length; i++) {
    const item = real_where[i]
    const key = fakeKey ? item[0].replace('.', '-') : item[0]
    if (item[1] === '=') {
      ret = obj[key] === item[2]
    }
    if (item[1] === '>') {
      ret = obj[key] > item[2]
    }
    if (item[1] === '<') {
      ret = obj[key] < item[2]
    }
    if (item[1] === '>=') {
      ret = obj[key] >= item[2]
    }
    if (item[1] === '<=') {
      ret = obj[key] <= item[2]
    }
    if (item[1] === '!=') {
      ret = obj[key] !== item[2]
    }
    if (item[1] === 'in') {
      ret = item[2].indexOf(obj[key]) !== -1
    }
    if (item[1] === 'not_in') {
      ret = item[2].indexOf(obj[key]) === -1
    }
    if (!ret) {
      return false
    }
  }
  return ret
}
```

## 列渲染

渲染类型:

- `number`、`switch`、`input`
- `icon`、`image`、`extrude`、`tag`、`link`、`iframe`、`html`

### number

**渲染条件**： `'edit' => true`  且 满足when中定义的条件

**用法**：

```php
'type' => 'number',
'edit' => true`,
'when' => [
    ['status', '=', Activity::STATUS_OFF]
],
```

### switch

**渲染条件**： `'edit' => true`  且 满足when中定义的条件

**用法**：

```php
'type' => 'switch',
'edit' => true`,
'when' => [
    ['status', '=', Activity::STATUS_OFF]
],
```

### input

**渲染条件**： `'edit' => true`  且 满足when中定义的条件
**用法**：

```php
'edit' => true`,
'when' => [
    ['status', '=', Activity::STATUS_OFF]
],
```

::: warning 注意
目前行编辑的表单组件只支持以上三种类型，且不能定义该组件的props
:::

### icon

**渲染条件**： `'type' => 'icon'`

**效果展示**：<i class="omsfont">&#xe65d;</i>

### image

**渲染条件**： `'type' => 'image'`，数据为数组时可以渲染多张图片

**效果展示**：![u](http://km.innotechx.com/download/attachments/82253382/image2020-5-8_14-6-56.png?version=1&modificationDate=1588918016653&api=v2)

#### extrude

**用法**：

```php
[
    "field" => "field_1",
    "type" => 'extrude',
    "render" => function($field_value, $row) {
      //  return "<优惠券|balck|yellow>*****"; 单个
      // or 支持多个
      return [
        "<优惠券{replace_field}|balck|yellow>*****{replace_field}"
      ]; // 格式 <文字|背景色|文字色>, 支持前端变量替换
    }
]
```

**效果展示**：![o](http://km.innotechx.com/download/attachments/47482497/Snipaste_2020-02-20_09-19-03.png?version=1&modificationDate=1582161564093&api=v2)

### tag

**用法**：

```php
[
    "field" => "field_1",
    "options" => [
        0 => '禁用',
        1 => '启用',
    ],
    "enum" => [ // tag 样式, 参见 https://element.eleme.io/#/zh-CN/component/tag
      0 => 'info',
      1 => 'success'
    ]
]
```

**效果展示**：![p](http://km.innotechx.com/download/attachments/47482497/Snipaste_2020-02-20_09-27-03.png?version=1&modificationDate=1582162039284&api=v2)

### link

**用法**：

```php
[
    "field" => "field_1",
    "href" => "http://hyperfadmin.com/page/{id}"
]
// or
[
    "field" => "field_1",
    "href" => [
      "href" => "http://hyperfadmin.com/page/{id}",
      "type" => "primary",
      "target" => "_blank"
    ]
]
```

**效果展示**：![p](http://km.innotechx.com/download/attachments/47482497/Snipaste_2020-02-20_09-22-39.png?version=1&modificationDate=1582161776808&api=v2)

### iframe

**用法**：

```php
[
    'field' => 'state_info',
    'title' => '运行状态',
    'type' => 'iframe',
    // 列以button形式显示，style控制按钮type, [info|success|primary|danger|warning]
    'style' => 'primary', 
    // 弹出窗口宽度,默认500px
    'width' => '500px',
    // 弹出窗口高度,默认600px
    'height' => '600px'
    "render" => function($field_value, $row) {
      return "url";// 返回 iframe的src路径
    }
]
```

### html

**用法**：

```php
[
    'field' => 'state_info',
    'title' => '运行状态',
    'type' => 'html',
    "render" => function($field_value, $row) {
      return '<p>xxxxxxx<br>xxxxx</p>'; // 
    }
]
```

### supperButton

**用法**：

```php
[
    'field' => 'field',
    'type' => 'supperButton',
    // supperButton的配置信息 必须有
    // 配置文档详见supperButton部分
    'config' => {

    }
]
```

### popover弹出框

支持以上除编辑模式下的列渲染类型

**用法**：

```php
[
    'field' => 'state_info',
    'title' => '运行状态',
    'popover' => [
        'messages' => [
            '上线时间: {state.start_time}',
            '最后活跃时间: {state.last_time}',
            '运行次数: {state.counter}',
        ],
        'when' => ["field_1", ">", 1],
        // or
        'when' => [
            ["field_1", ">", 1],
            ["field_2", "=", 1]
        ]
    ]
]
```

**效果展示**：![popover弹出框](http://qupinapptest.oss-cn-beijing.aliyuncs.com/img/Snipaste_2020-02-21_18-06-06.png)

### progress

**用法**：

```php
[
    'field' => 'sync_progress',
    'title' => '同步进度',
    'type' => 'progress',
    // 'props' => []
]
```

> props里是progress组件的属性配置，请参考[Progress 进度条](https://element.eleme.cn/#/zh-CN/component/progress#attributes)

**效果展示**：![Progress 进度条](http://qupinapptest.oss-cn-beijing.aliyuncs.com/img/Snipaste_2020-02-21_18-06-06.png)


## 搜索项

```php
[
    ......
    // 搜索条件, 前端页面会根据此处配置渲染搜索条件，可以像表单一样配置规则
    // 搜索条件中支持模糊搜索也很简单 %field_name%, field_name%, %field_name, 如此定义字段即可
    'filter' => [
        'id', 
        'username%', 
        'create_at|创建时间' => [
            'type' => 'date_range',
            'search_type' => 'between',
            'default' => [date('Y-m-d', time() - DAY * 7), date('Y-m-d', time() + DAY)]
        ]
    ],
]
```

## tab切换

```php 
'tabs' => [
    [
        'label' => '平台券',
        'value' => (string) Coupon::TYPE_PLATFORM_MONEY_OFF,
        'icon' => 'el-icon-s-grid',
    ],
    [
        'label' => '店铺券',
        'value' => (string) Coupon::TYPE_MALL_MONEY_OFF,
        'icon' => 'el-icon-s-grid',
    ],
],
'columns' => [ 
    [
        'field' => 'state_info',
        'title' => '运行状态',
        // 定义该字段显示在哪些tab选项中
        'depend' => [
            'tab' => [(string)Coupon::TYPE_MALL_MONEY_OFF],
        ],
    ]
]
```

**效果展示**：![](http://qupinapptest.oss-cn-beijing.aliyuncs.com/img/Snipaste_2020-02-21_19-46-14.png)

## 操作按钮

支持 列操作按钮, 批量操作按钮, 列表页顶部按钮，前端组件为`SuperButton`

### 基础属性

```php
[
    "text" => "", //按钮文案，支持参数替换
    "type" => "jump", // 按钮类型 默认 jump, 可选 form, api
    "target" => "", // 动作目标 本地路由, 网址, 后端api，支持参数替换
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

### 普通跳转按钮

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

### 动作按钮(请求后端API)

点击按钮后, 提示二次确认, 确认后请求后端api

```javascript
[
    "text" => "删除",
    "target" => "/user/12",
    "method" => "POST", // 请求api的方式, 默认POST
]
```

### 表单型按钮

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

### 列表型按钮

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

### 抽屉型按钮

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

### 下拉按钮

上面 SuperButton 的结构改为数组形式即可

