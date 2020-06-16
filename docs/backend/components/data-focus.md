数据大盘

DataFocus (焦点数据) 用途是帮助大家快速构建一个如下的数据面板, 对数据可视化做了大量封装, 让开发者只用关心数据来源和数据处理, 无需处理复杂的图标构建, 即可轻松制作出漂亮的看板, 为业务决策这提供更直观的参考.

数据面板的定义

数据面板中可以定义, `sql`, `json`, `php代码`, `markdown` , `html` 等级数据格式, 通过统一转换由相应前端组件渲染成`图标` 或`列表`

目前支持的图标样式`LineChart`, `ColumnChart`, `PieChart`, `NumberPanel` 分别会渲染为`曲线图`, `柱状图`, `饼图`, `数字面板`, `列表`

### sql节点

1.  定义改节点的属性
    1.  id 节点名称, 必须, 不可重复
    2.  dsn sql 查询说使用的dsn, 可用dsn的范围是 hyperf/config/databaes 中 DataFocus Dsn 中定义的数据源
    3.  chart 图标类型, 格式为`图表名|X轴,Y轴1,Y轴2,…`, 图标名为必须
    4.  show_table 默认 `false`, 为`true`时除了渲染图表, 还会渲染数据列表
    5.  table_plugin 表级的插件, 可以对结果数据做二次干预, 可以调用 DataFocus/plugin_fucntion 中定义的全局插件, 也可在调用当前面板中自定义插件
        1.  自定义插件为 当前页面的一段`php function`代码
        2.  面板中的所有自定义`php`方法, 必须以`df_`开头
    6.  span 布局 总24的栅格布局, 具体参见
2.  节点内容, 填写构造数据的查询`sql`即可
    1.  一个`<sql></slq>` 节点 只能定义一个`sql`语句
    2.  只能使用`select`语句

下面的样例中定义的一个以日期`date`为`X轴`, 其他数据指标为`Y轴`的曲线图

```php
<sql id="近30日访问趋势" dsn="hyperf_admin" chart='LineChart|date' >
	select 
		visitor_uv as "人数",
		visitor_pv as "次数"
		data_date as date
	from 
		visitor_log 
	where 
		data_date >= {{ date('Y-m-d', strtotime('-30 day')) }}
	group by date
</sql>
```

其他类型的图标也基本类似, 只用调整相应的`chart`数据即可, 比如下面的饼图

```php
<sql id="今日访问地区占比" dsn="rock_admin" chart="PieChart" table_plugin="df_list_transposition:地区,数量" span="12">
	select 
		area as "地区",
		count(1) as "数量"
	from 
		visitor_log 
	where 
		data_date  >= {{ date('Y-m-d', strtotime('-1 day')) }}
	group by area
</sql>
```

3. 模板变量

细心的同学可能已经发现, 上面的`sql`内容中使用了`{{ date('Y-m-d', strtotime('-1 day')) }}`这样的变量定义,模板变量的定义类似 `twig` 语法, 本质上是把花括号的内容转换为`php`代码进行运算, 然后替换模板变量, 执行`sql`.

格式:  `{{ func|pip1|pip2 }}`

`func` 为数据的产生源头, 是必须的. 后面`|` 竖线分隔的管道, 让会源头输出的结构做二次处理, 最终替换到模板中

次数模板替换时, 会默认给变量加引号, 比如上方的`sql` 最终会替换为 `data >= '2020-06-11'`, 若需要原样输出, 可只用 `raw` 管道

管道也可以是一个自定义`php` 方法

更多样例见`系统管理/DataFocus/数据面板` 菜单下

### json

节点属性同 sql

节点内容, 可以填入`json`格式数据

```php
<json>
{
  "lable":"value"
}
</json>
```

### php

php并非一个单独节点, 而是可以作为一个片段, 迁移任意节点内

```php
<json>
<?php return json_encode(df_******());?>
</json>
```

### md

### html