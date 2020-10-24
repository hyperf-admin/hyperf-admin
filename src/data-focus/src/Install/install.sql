-- cron-center db

CREATE TABLE `dsn` (
    `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
    `type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '类型, 1 mysql',
    `name` varchar(50) NOT NULL COMMENT '名称',
    `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
    `config` tinytext NOT NULL COMMENT '配置',
    `create_uid` int(12) unsigned NOT NULL DEFAULT '0' COMMENT '创建者id',
    `status` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '0启用, 1禁用',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `plugin_function` (
    `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL COMMENT '中文名称',
    `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '方法类型 1列插件, 2表插件',
    `func_name` varchar(50) NOT NULL COMMENT '方法名',
    `context` text COMMENT '方法体定义',
    `create_uid` int(120) unsigned NOT NULL DEFAULT '0' COMMENT '创建者id',
    `status` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `func_name` (`func_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `report_change_log` (
    `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
    `report_id` int(12) unsigned NOT NULL COMMENT '报表id',
    `dev_uid` int(12) unsigned NOT NULL COMMENT '开发者id',
    `dev_content` text COMMENT '内容',
    `published` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '0未发布, 1已发布',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `report_id` (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `reports` (
    `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
    `pid` int(12) unsigned NOT NULL COMMENT '父id',
    `publish_content` text COMMENT '发布的报表内容',
    `dev_content` text COMMENT '开发中的报表内容',
    `bind_rold_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '授权的角色id',
    `bind_uids` varchar(255) NOT NULL DEFAULT '' COMMENT '绑定的用户id',
    `create_uid` int(12) unsigned NOT NULL COMMENT '创建者id',
    `dev_uid` int(12) unsigned NOT NULL COMMENT '开发者id',
    `crontab` varchar(255) NOT NULL DEFAULT '' COMMENT '定时任务',
    `config` text COMMENT '配置',
    `publish_at` datetime DEFAULT NULL COMMENT '最后一次发布时间',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

