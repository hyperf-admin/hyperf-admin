通用配置在只需要使用表单搜集信息, 没有过多业务逻辑和校验规则时使用, 可以无需开发, 完成表单的定义和使用

1.  定义表单 http://localhost:9528/system/#/cconf/list, 此处表单规则同控制器中的form定义

    ![](http://qupinapptest.oss-cn-beijing.aliyuncs.com/img/DGD103.png)

2.  投放表单, 创建 `/***/cconf_{1中的表单名称}` 即可通过该路由访问

    ![](http://qupinapptest.oss-cn-beijing.aliyuncs.com/img/b9Pw1z.png)

3.  访问 http://localhost:9528/hyperf/#/lucky/cconf_lucky_round

    ![](http://qupinapptest.oss-cn-beijing.aliyuncs.com/img/I3pgnz.png)

数据存储位置`hyperf_admin.common_config`

```mysql
CREATE TABLE `common_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `namespace` varchar(50) NOT NULL DEFAULT '' COMMENT '命名空间, 字母',
  `name` varchar(100) NOT NULL COMMENT '配置名, 字母',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '可读配置名',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `rules` text COMMENT '配置规则描述',
  `value` text COMMENT '具体配置值 key:value',
  `permissions` text COMMENT '权限',
  `create_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`name`,`namespace`),
  KEY `namespace` (`namespace`),
  KEY `update_at` (`update_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通用配置';
```

可以通过 http://localhost:9528/system/#/cconf/list 管理命名空间

系统中可以使用`Rcok\BaseUtils\Service\CommonConfig`获取相应的提交数据
