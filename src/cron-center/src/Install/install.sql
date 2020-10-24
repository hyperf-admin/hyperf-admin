-- data-focus db

CREATE TABLE `dsn` (
    `id`         INT(12) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `type`       TINYINT(4) UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型, 1 mysql',
    `name`       VARCHAR(50)         NOT NULL COMMENT '名称',
    `remark`     VARCHAR(255)        NOT NULL DEFAULT '' COMMENT '备注',
    `config`     TINYTEXT            NOT NULL COMMENT '配置',
    `create_uid` INT(12) UNSIGNED    NOT NULL DEFAULT '0' COMMENT '创建者id',
    `status`     TINYINT(4) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0启用, 1禁用',
    `created_at`  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = `utf8mb4`;

CREATE TABLE `plugin_function` (
    `id`         INT(12) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(50)         NOT NULL COMMENT '中文名称',
    `type`       TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '方法类型 1列插件, 2表插件',
    `func_name`  VARCHAR(50)         NOT NULL COMMENT '方法名',
    `context`    TEXT COMMENT '方法体定义',
    `create_uid` INT(120) UNSIGNED   NOT NULL DEFAULT '0' COMMENT '创建者id',
    `status`     TINYINT(4) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态',
    `created_at`  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME                     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `func_name` (`func_name`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = `utf8mb4`;

CREATE TABLE `report_change_log` (
    `id`          INT(12) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `report_id`   INT(12) UNSIGNED    NOT NULL COMMENT '报表id',
    `dev_uid`     INT(12) UNSIGNED    NOT NULL COMMENT '开发者id',
    `dev_content` TEXT COMMENT '内容',
    `published`   TINYINT(4) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0未发布, 1已发布',
    `created_at`   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `report_id` (`report_id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = `utf8mb4`;

CREATE TABLE `reports` (
    `id`              INT(12) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`            VARCHAR(50)      NOT NULL DEFAULT '' COMMENT '名称',
    `pid`             INT(12) UNSIGNED NOT NULL COMMENT '父id',
    `publish_content` TEXT COMMENT '发布的报表内容',
    `dev_content`     TEXT COMMENT '开发中的报表内容',
    `bind_rold_ids`   VARCHAR(255)     NOT NULL DEFAULT '' COMMENT '授权的角色id',
    `bind_uids`       VARCHAR(255)     NOT NULL DEFAULT '' COMMENT '绑定的用户id',
    `create_uid`      INT(12) UNSIGNED NOT NULL COMMENT '创建者id',
    `dev_uid`         INT(12) UNSIGNED NOT NULL COMMENT '开发者id',
    `crontab`         VARCHAR(255)     NOT NULL DEFAULT '' COMMENT '定时任务',
    `config`          TEXT COMMENT '配置',
    `publish_at`      DATETIME                  DEFAULT NULL COMMENT '最后一次发布时间',
    `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新时间',
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = `utf8mb4`;

