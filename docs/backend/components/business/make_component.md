### 需求提炼

`hyperf` 项目启动后, 会将`config` 目录下的配置信息, 统一载入到 `Config` 对象中, 以供框架使用, 但对于部分业务性的配置依赖, 如果我们想修改, 必须`修改代码->提交pr->合入主干->线上部署`等一系列的工作, 如果我们能实现一个`配置中心`, 只要在后台修改下指定配置, 然后线上各进程中的`Config`对象做到自动更新, 那么就会极大的进化工作流程. 
所以, 让我们来一起实现一个`config-center`配置中心的组件吧. 

拆解如下:
1. 底层存储可以基于 `Consul` 或 `Nacos` 或 `Redis` 
    1. 考虑到低成本实现, 最终决定用`Redis`, 如果多语言交互的话, 建议使用 `Consule/Nacos`等配置中心
2. 配置的修改, 发布后可以实时更新到线上的工作进程
3. 提供通用管理页面, 无差别化底层的存储
4. 额外再提供一个基于配置中心的`switch_service`开关服务

基本思路:

1.  配置存放在mysql, 有 `insert` 或 `update` 时, 保存聚合数据到`Redis`
2.  `woker` 启动时, 合并代码中的`config` 和 配置中心的配置, 加载到内存
3.  `worker`进程中增加`timer`, 当缓存数据有更新时, 加载新数据到内存, 以供业务使用

### 开动吧

#### 1. 确定表结构

```sql
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
```

#### 2. 创建 `Controller`, `Model`

![wlXctg](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/wlXctg.png)

此时基础文件已经生成在`lib/` 下, 作为一个`composer`包模式开发, 我们将其转移至 `/opt/hyperf-admin/config-center`目录

然后 `cd /opt/hyperf-admin/config-center` 执行 `composer init`, 初始包文件

然后, 调整依赖 `hyperf-admin/admin`, `ConfigProvider`, `Insert Command` 等, 此时的目录结构如下

```shell
./
├── composer.json
└── src
    ├── ConfigProvider.php
    ├── Controller
    │   └── ConfigCenterController.php
    ├── Install
    │   ├── InstallCommand.php
    │   └── install.sql
    └── Model
        └── ConfigCenter.php
```

接下来回来主项目, 修改 composer.json 的 `repositories`

```json
"repositories": [
   {
       "type": "path",
       "url": "/opt/hyperf-admin/config-center"
   }
]
```

安装`config-center`的开发包 `composer require hyperf-admin/config-center`

#### 3. 添加菜单 注册路由

![uAR5lj](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/uAR5lj.png)

```php
<?php

use HyperfAdmin\ConfigCenter\Controller\ConfigCenterController;

register_route('/config_center', ConfigCenterController::class);
```

`ConfigProvider` 中注册该文件路径到 `init_routes`节点

?> 若配置有不生效的情况, 执行 `rm vendor/hyperf-admin/config-center && composer require hyperf-admin/config-center` 重新安装即可

![EFvajy](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/EFvajy.png)

至此已经完成了, 配置的`CRUD`.

#### 4. 配置变动的`Redis`更新

`ConfigCenterService` 将配置从`mysql`中捞出, 聚合后, 存入`Redis`中, 并更新相应版本号

`ConfigCenterController.afterSave` 钩子中, 调用 `ConfigCenterService`

#### 5. 工作进程中的配置自动更新

`BootProcessListener` 中监听`BeforeWorkerStart`, `BeforeProcessHandle`, `BeforeHandle`事件, 定设置定时器, 定时同步配置.

有了配置中心我们可以做的很有想象空间了, 比如线上db信息的保密, 动态控制自定义进程的启用禁用(结合服务重启), 等等.

完整代码结构如下, 具体源码在`src/config-center`目录 [这里](https://github.com/hyperf-admin/hyperf-admin/tree/master/src/config-center)

```shell
./
├── composer.json
└── src
    ├── BootProcessListener.php
    ├── ConfigProvider.php
    ├── Controller
    │   └── ConfigCenterController.php
    ├── Install
    │   ├── InstallCommand.php
    │   └── install.sql
    ├── Model
    │   └── ConfigCenter.php
    ├── Service
    │   └── ConfigCenterService.php
    └── routes.php
```

