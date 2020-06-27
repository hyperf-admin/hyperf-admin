我们通过一个具体案例, 来看看如何应用`hyperf-admin`快速实现

### 1.需求描述

​	实现一个某校年级内各班级学生的各科成绩管理后台, 要求如下

1.  列表显示学生 年级,班级,学习,学科,成绩,时间,性别,年龄
2.  可以按成绩 倒序/正序排列
3.  可以批量导入/导出学生成绩
4.  可以通过 年级,学生名称,班级 等条件筛选
5.  最好列表可以分页签直接显示各科成绩
6.  没有原型图

### 2. 数据库定义

这里不做太复杂的设计, 仅用一张表来完成此需求

```sql
CREATE TABLE `student_score` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `grade` tinyint(4) unsigned NOT NULL COMMENT '年级',
  `class` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '班级',
  `subject` tinyint(4) unsigned NOT NULL COMMENT '学科',
  `score` int(12) unsigned NOT NULL DEFAULT '0' COMMENT '分数',
  `name` varchar(10) NOT NULL DEFAULT '' COMMENT '学生名称',
  `sex` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '性别, 0女生, 1男生',
  `create_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

接下来在`MySql`中创建表

### 3.功能开发

1.  `hyperf`中添加db信息

    ```php
    // config/autoload/databases.php
    'local' => db_complete([
        'host' => '127.0.0.1',
        'database' => 'test',
        'username' => 'root',
        'password' => 'root'
    ])
    ```

2.  通过`DevTools`开发者工具创建 `student_score` 相关的 `Model`, `Controller`

    ![YPdEli](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/YPdEli.png)

    选择好相应的表后, 点击提交, 此时工具已经帮我们创建好相应的`app/Controller/StudentScoreController.php`和`app/Model/Test/StudentScore.php`

3.  添加目录和菜单

    ![cs0SYX](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/cs0SYX.png)

    注册路由

    ```php
    // config/routes.php
    register_route('/student_score', StudentScoreController::class);
    ```

    此时我们也已经完成了基础的`CRUD`开发

    ![MEoM4p](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/MEoM4p.png)

    哦对了, 还有各种筛选条件呢? 也很简单, 在 `scaffoldOptions` 中增加 `filter`配置即可

    ```php
    public function scaffoldOptions()
    {
      return [
        'filter' => [
          'grade', 'class', 'subject', 'name%',
          'score|分数' => [
            'type' => 'input-range',
            'select_type' => 'between'
          ]
        ],
      ];
    }
    ```

    ![u68v1D](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/u68v1D.png)

    还有, 大家别忘了, 需求中还要去可以按页签显示, 改怎么办呢, 这个ui可有点复杂啊, 不过在`hyperf-admin`里也同样简单

     `scaffoldOptions` 中增加 `table.tabs`配置即可

    ```php
    public function scaffoldOptions()
    {
      return [
        'table' => [
          	'tabs' => [
                  [
                    'label' => '语文',
                    'value' => 1,
                    'icon' => 'el-icon-s-grid',
                  ],
                  [
                    'label' => '数学',
                    'value' => 2,
                    'icon' => 'el-icon-s-grid',
                  ],
            ]
        ],
      ];
    }
    ```

    ![Ax0WWD](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/Ax0WWD.png)

至此我们已经完成了绝大部分的功能开发, 如果使用熟练, 我们应该能在十分钟内完成整个功能的前后端开发, 而且还支持复杂的前端效果.

?> 当然`hyperf-admin`还支持更多复杂的功能, 快快用你明亮的眼睛去发现他吧.
