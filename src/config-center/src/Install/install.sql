
CREATE TABLE `config_center` (
    `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
    `path` varchar(255) NOT NULL DEFAULT '' COMMENT '存储位置, . 分隔',
    `value` text COMMENT '节点值',
    `create_uid` int(12) NOT NULL COMMENT '创建者id',
    `is_locked` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否被锁定',
    `owner_uids` varchar(255) NOT NULL COMMENT '所有者, 逗号分隔',
    `create_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `update_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
