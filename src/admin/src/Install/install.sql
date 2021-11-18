-- rock-admin db 安装

CREATE TABLE `common_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `namespace` varchar(50) NOT NULL DEFAULT '' COMMENT '命名空间, 字母',
  `name` varchar(100) NOT NULL COMMENT '配置名, 字母',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '可读配置名',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `rules` text COMMENT '配置规则描述',
  `value` text COMMENT '具体配置值 key:value',
  `permissions` text COMMENT '权限',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_need_form` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用表单：0，否；1，是',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`name`,`namespace`),
  KEY `namespace` (`namespace`),
  KEY `updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通用配置';

CREATE TABLE `export_tasks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '任务名称',
  `list_api` varchar(255) NOT NULL COMMENT '列表接口',
  `filters` text COMMENT '过滤条件',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '任务状态: 0未开始, 1进行中, 2已完成, 3失败',
  `total_pages` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '总页数',
  `current_page` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '当前页',
  `operator_id` int(11) unsigned NOT NULL COMMENT '管理员id',
  `download_url` varchar(100) NOT NULL DEFAULT '' COMMENT '下载地址',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `host_ip` varchar(50) DEFAULT '' COMMENT '主机ip',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `front_routes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `label` varchar(50) NOT NULL DEFAULT '' COMMENT 'label名称',
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT '模块',
  `path` varchar(100) NOT NULL DEFAULT '' COMMENT '路径',
  `view` varchar(100) NOT NULL DEFAULT '' COMMENT '非脚手架渲染是且path路径为正则时, vue文件路径',
  `icon` varchar(50) NOT NULL DEFAULT '' COMMENT 'icon',
  `open_type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '打开方式 0 当前页面 2 新标签页',
  `is_scaffold` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '是否脚手架渲染, 1是, 0否',
  `is_menu` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否菜单 0 否 1 是',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0 禁用 1 启用',
  `sort` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '排序，数字越大越在前面',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `permission` text NOT NULL COMMENT '权限标识',
  `http_method` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '请求方式; 0, Any; 1, GET; 2, POST; 3, PUT; 4, DELETE;',
  `type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '菜单类型 0 目录  1 菜单 2 其他',
  `page_type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '页面类型： 0 列表  1 表单',
  `scaffold_action` varchar(255) NOT NULL DEFAULT '' COMMENT '脚手架预置权限',
  `config` text COMMENT '配置化脚手架',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COMMENT='前端路由(菜单)';

CREATE TABLE `global_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namespace` varchar(50) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  `remark` varchar(100) NOT NULL DEFAULT '',
  `value` longtext,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`),
  KEY `namespace` (`namespace`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='全局配置';

CREATE TABLE `role_menus` (
  `role_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '角色ID',
  `router_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '路由ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `role_router_id` (`role_id`,`router_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
  `permissions` text NOT NULL COMMENT '角色拥有的权限',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0 禁用 1 启用',
  `sort` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '排序，数字越大越在前面',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色表';

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
  `realname` varchar(50) NOT NULL DEFAULT '',
  `password` char(40) NOT NULL,
  `mobile` varchar(20) NOT NULL DEFAULT '',
  `email` varchar(50) NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `login_time` timestamp NULL DEFAULT NULL,
  `login_ip` varchar(50) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'is admin',
  `is_default_pass` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否初始密码1:是,0:否',
  `qq` varchar(20) NOT NULL DEFAULT '' COMMENT '用户qq',
  `roles` varchar(50) NOT NULL DEFAULT '10',
  `sign` varchar(255) NOT NULL DEFAULT '' COMMENT '签名',
  `avatar` varchar(255) NOT NULL DEFAULT '',
  `avatar_small` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `user_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `role_id` int(11) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role_id` (`user_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `operator_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_url` varchar(50) DEFAULT '' COMMENT '页面url',
  `page_name` varchar(50) DEFAULT '' COMMENT '页面面包屑/名称',
  `action` varchar(50) DEFAULT '' COMMENT '动作',
  `operator_id` int(11) DEFAULT '0' COMMENT '操作人ID',
  `nickname` varchar(50) DEFAULT '' COMMENT '操作人名称',
  `relation_ids` text COMMENT '多个id-当前版本ID[id-current_version_id,]',
  `detail_json` text COMMENT '需要灵活记录的json',
  `client_ip` varchar(50) DEFAULT '' COMMENT '客户端地址',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4511 DEFAULT CHARSET=utf8 COMMENT='通用操作日志';
