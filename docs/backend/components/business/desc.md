### 基础结构

业务组件是一个可用功能的最小单元, 这个功能必须具有一定的通用性, 一般包含`控制器`, `Model`, `安装命令`, `DB`, `路由`, `ConfigProvider`

将这个单一功能封装为通用的业务组件后, 它将可以在任何基于`hyperf-amdin`构建的项目中引用, 已达到更高层次的复用.

关于基础组件的选择

```shell
# 若组件强依赖用户信息, 请基于 admin 开发
composer require hyperf-admin/admin

# 若不依赖用户信息, 请基于 base-utils 开发
composer require hyperf-amdin/base-utils
```

下面是`data-focus` 数据面板组件的完整结构

```shell
├── README.md
├── composer.json
└── src
    ├── ConfigProvider.php
    ├── Controller
    │   ├── DsnController.php
    │   ├── PluginFunctionController.php
    │   ├── ReportChangeLogController.php
    │   └── ReportsController.php
    ├── Install
    │   ├── InstallCommand.php
    │   └── install.sql
    ├── Model
    │   ├── Dsn.php
    │   ├── PluginFunction.php
    │   ├── ReportChangeLog.php
    │   └── Reports.php
    ├── Service
    │   └── Dsn.php
    ├── Util
    │   ├── BootAppConfListener.php
    │   ├── CodeRunner.php
    │   ├── PHPSandbox.php
    │   ├── SandboxException.php
    │   ├── SimpleHtmlDom.php
    │   ├── ValidatorVisitor.php
    │   └── func.php
    ├── config
        └── routes.php
```

其他的业务组件也基本于此类似. 

### 安装与使用

```shell
composer require hyperf-admin/****

php bin/hyperf.php
```

可以查看到相关安装命令

![qIKtC8](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/qIKtC8.png)

开发环境执行相应命令, 安装依赖的`db`结构即可, 在此之前请先确认`.env` 中已配置好相应连接信息.

若是生成环境, 应该联系`DBA`操作, 并提供相应目录下的`sql`文件.

然后将相应的菜单添加到后台即可使用.

!> 这里的组件菜单, 后期可以优化成配置文件导入的方式, 会更简单些

业务组件的db依赖问题, 参见 `src/cron-center/src/ConfigProvider.php` 中 `databases`

```php
'databases' => [
    'config_center' => db_complete([
        'host' => env('CONFIG_CENTER_DB_HOST', env('HYPERF_ADMIN_DB_HOST')),
        'database' => env('CONFIG_CENTER_DB_NAME', env('HYPERF_ADMIN_DB_NAME')),
        'username' => env('CONFIG_CENTER_DB_USER', env('HYPERF_ADMIN_DB_USER')),
        'password' => env('CONFIG_CENTER_DB_PWD', env('HYPERF_ADMIN_DB_PWD')),
    ]),
],
```

组件可以使用自己单独的库配置, 默认使用 `hyperf_amdin` 的主db配置.

