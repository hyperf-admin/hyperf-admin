## 字段规则

```php
'field|字段名称' => [
    // 字段验证规则,
    'rule' => 'required|max:',
    // 请参考http://www.form-create.com/v2/element-ui/components/input.html
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
    // 是否虚拟字段，虚拟字段在查询脚手架model时，会忽略该字段
    'virtual_field' => true,
    // 该字段在表单中是否渲染，默认true
    'form' => false,
],
```

*rule: 后端字段验证规则*

rule完整支持 hyperf 原生的 validation 的校验[规则]([https://hyperf.wiki/#/zh-cn/validation?id=%e9%aa%8c%e8%af%81%e8%a7%84%e5%88%99](https://hyperf.wiki/#/zh-cn/validation?id=验证规则)), 且切封装了高度灵活的自定义校验 `app/Service/ValidationCustomRule.php` 其中定义的方法均可在`rule` 中直接使用, 还支持`cb_***` 调用定义在当前控制器中的自定义验证.

> 注意：目前还没有根据该规则生成前端的验证规则，前端目前只验证了是否必填的

## 内置组件

type：表单项类型，以下是支持的组件列表，以下所有组件 props 均可支持原始文档中的所有属性

### 1. 普通输入框

[原始文档](http://www.form-create.com/v2/iview/components/input.html)

```php
[
  "field_name|字段名" => "required|***"
  // or
  "field_name|字段名" => [
    "type" => "input" // 可省略, 默认 input
    "rule" => "required|***",
    "default" => "默认值" // 非必须,
    "info" => "字段提示文字",
    "props" => [
      "showCopy" => true, // 开启 copy 功能
    ]
  ]
]
```

### 2. 数字(整数)

[原始文档](http://www.form-create.com/v2/iview/components/input-number.html)

```php
[
  "field_name|字段名" => [
    "type" => "number"
  ]
]
```

### 3. 数字(两位小数)

[原始文档](http://www.form-create.com/v2/iview/components/input-number.html)

```php
[
  "field_name|字段名" => [
    "type" => "float",
    "props" => [
      "precision" => 2, // 小数保留位数, 默认2
    ]
  ]
]
```

### 4. 多行输入

[原始文档](http://www.form-create.com/v2/iview/components/input.html)

```php
[
  "field_name|字段名" => [
    "type" => "textarea",
    "props" => [
      "row" => 6, // 行数, 默认6
    ]
  ]
]
```

### 5. 开关(0/1)

[原始文档](http://www.form-create.com/v2/iview/components/switch.html)

```php
[
  "field_name|字段名" => [
    "type" => "switch"
  ]
]
```

### 6. 单选框

```php
[
  "field_name|字段名" => [
    "type" => "radio",
    "options" => [
      	value1 => "label1",
        value2 => "label2"
    ]
  ]
]
```

### 7.复选框

```php
[
  "field_name|字段名" => [
    "type" => "checkbox",
    "options" => [
      	value1 => "label1",
        value2 => "label2"
    ]
  ]
]
```

### 8. 时间控件

[原始文档](http://www.form-create.com/v2/iview/components/date-picker.html)

```php
[
  "field_name|字段名" => [
    "type" => "datetime"
  ]
]
```

### 9. 时间区间

[原始文档](http://www.form-create.com/v2/iview/components/date-picker.html)

```php
[
  "field_name|字段名" => [
    "type" => "datetime_range",
    "props" => [
        "range" => [
            "after" => date('Y-m-d'),
            "before" => date('Y-m-d', strtotime('+6 days'))
        ],
        // or 简写
        "range" => "afterToday", // afterToday 今天之后, beforeToday 今天之前, 包含今天
    ]
  ]
]
```

### 10. 日期

[原始文档](http://www.form-create.com/v2/iview/components/date-picker.html)

```php
[
  "field_name|字段名" => [
    "type" => "date"
  ]
]
```

### 11. 日期区间

[原始文档](http://www.form-create.com/v2/iview/components/date-picker.html)

```php
[
  "field_name|字段名" => [
    "type" => "date_range"
  ]
]
```

### 12. 下拉选择框

[原始文档](http://www.form-create.com/v2/iview/components/select.html)

```php
[
  "field_name|字段名" => [
    "type" => "select",
      "options" => [ // 远程搜索是无需 支持回调函数 function() { return 备选项; }
      1 => "lable1",
      2 => "lable2",
    ],
    "props" => [
      "selectApi" => "/coupon/act", // 远程搜索模式
      "multiple" => true, // 是否多选, 默认false
      "multipleLimit" => 10, // 多选时的上限
    ]
  ]
]
```

### 13. 上传图片

[原始文档](http://www.form-create.com/v2/iview/components/upload.html)

```php
[
  "field_name|字段名" => [
    "type" => "image",
    "props" => [
      // 上传张数上线, 默认1单个
      "limit" => 1,
      //支持下载
      "downloadable" => true,
      // 限制上传文件的后缀名
      "format " => ['jpg', 'jpeg', 'png', 'gif'],
      // 限制上传文件的大小 单位是 kb
      "maxSize" => 200,
      // 上传的目标 bucket
      'bucket' => 'aliyuncs',
      // 是否为私有
      'private' => true,
    ]
  ]
]
```

### 14. 上传文件

[原始文档](http://www.form-create.com/v2/iview/components/upload.html)

```php
[
  "field_name|字段名" => [
    "type" => "file",
    "props" => [
      // 上传张数上线, 默认1单个
      "limit" => 1,
      //支持下载
      "downloadable" => true,
      // 限制上传文件的后缀名
      "format " => ['doc', 'exl', 'ppt'],
      // 限制上传文件的大小 单位是 kb
      "maxSize" => 200,
    ]
  ]
]
```

### 15. 级联选择器

```php
[
  "field_name|字段名" => [
    "type" => "cascader",
    "props" => [
      "limit" => 1, // 上传张数上线, 默认1单个
    ]
  ]
]
```

### 16. json 组件

```php
[
  "field_name|字段名" => [
    "type" => "json",
  ]
]
```

### 17. 富文本

```php
[
  "field_name|字段名" => [
    "type" => "html",
  ]
]
```

### 18. 图标选择器

```php
[
  "field_name|字段名" => [
    "type" => "icon-select",
  ]
]
```

示例效果：

![icon](http://qupinapptest.oss-cn-beijing.aliyuncs.com/1/202005/9e62be2e0affafc0cdee8bc7fba3c0fd.png)

### 19. 嵌套表单 SubForm

```php
'test|嵌套表单' => [
    'type' => 'sub-form',
    'children' => [ // 子表单的规则, 同一级规则
        'test_sub|嵌套1' => 'required',
        'test_sub1|嵌套2' => 'required',
    ],
    'repeat' => true, // 是否可动态添加
    'default' => [ // 默认值
        [
            'test_sub' => 1,
            'test_sub1' => 1,
        ],
        [
            'test_sub' => 1,
            'test_sub1' => 1,
        ],
    ],
],
```

示例效果：

![subForm](http://qupinapptest.oss-cn-beijing.aliyuncs.com/img/Snipaste_2020-02-21_19-29-35.png)

### 20. 区域输入框

[前端文档](http://localhost:8080/hyperfdoc/frontend/components/3_InputRange.html)

```php
'test|区域输入框' => [
    'type' => 'inputRange',
    // value值 type： Array or String
    // - Array：例如：[1, 10], 返回结果也将是数组
    // - String：例如：1,10, 返回结果也将是字符串
    'value' => [1, 10] or '1,10',
    'props' => {
      // 是否允许清除
      'clearable': true,
      // 可控制item宽度等样式 默认宽度300px
      'style': 'width: 300px',
      // 开始值和结束值的placeholder
      'placeholder': ['min', 'max']
    },
],
```

## 组件联动

```php
// depend
[
  "field_1" => "",
  "field_2" => [
    "depend" => [
      "field" => "field_1", // 依赖字段
      "value" => '1' // 当 field_1 = 1 时 field_2 此项才会显示
    ]
  ]
]

// hidden
[
  "field_1" => "",
  "field_2" => [
    "hidden" => [
      "field" => "field_1", // 影响字段
      "value" => '1' // 当 field_2 = 1 时 field_1 项会隐藏
    ]
  ]
]

// 备选项 条件控制
[
    "field_1" => [
        "type" => "select",
        "options" => [
            [
                "value" => 1,
                "lable" => "是"
            ],
            [
                "value" => 0,
                "lable" => "否",
                // 当 disabled_when 条件运算结果, 即为 disabled 的值
                "disabled_when" => [
                    "field_1", '=', 0
                ],
                // or
                "disabled_when" => [
                    ["field_2", '=', 0],
                    ["field_3", '!=', 4],
                ],
            ]
        ]
    ]
]

// 进阶用法 compute 动态计算
[
    "field" => [
        "compute" => [
            "when" => ['=', 1], // 注意这里只有个 比较操作符 和 比较值
            // set 操作项
            "set" => [
                "field_2" => [
                    // 此处支持控件除 type 外, 完整属性设置
                    "value" => 1,
                    // 此处支持 值为 callable
                    "value" => function() { return time();},
                    // 重写rule
                    "rule" => "required"
                    "props" => [
                        // ...
                    ]
                ],
                // ... "field_3"
            ],
            // append 项, 尚未实现
            // remove 项, 尚未实现
        ]
    ]
]
```
