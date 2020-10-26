`HyperfAdmin`是前后端分离的后台管理系统, 前端基于`vue`的 `vue-admin-template`, 针对后台业务`列表`, `表单`等场景封装了大量业务组件, 后端基于`hyperf`实现, 整体思路是后端定义页面渲染规则, 前端页面渲染时首先拉取配置, 然后组件根据具体配置完成页面渲染, 方便开发者仅做少量的配置工作就能完成常见的`CRUD`工作, 同时支持自定义组件和自定义页面, 以开发更为复杂的页面.

[详细文档](https://hyperf-admin.github.io/hyperf-admin/)

[演示站点](http://hyperf-admin.daodao.run/) `用户名`: daodao, `密码`: a1a1a1

!> 演示站点部署在`亚马逊免费主机`, 国内访问可能会慢

![HyperfAdmin架构](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/sJaJti.png)

前端为`vue multiple page`多页模式, 可以按模块打包, 默认包含两个模块`default` 默认模块,  `system`系统管理模块,  绝大部分业务组件在`src/components`目录

后端为`composer包`模式, 目前包含组件

-   基础组件
    -   `composer require hyperf-admin/base-utils` hyperf-admin的基础组件包, 脚手架主要功能封装
    -   `composer require hyperf-admin/validation` 参数验证包, 对规则和参数提示做了较多优化
    -   `composer require hyperf-admin/alert-manager` 企微/钉钉机器人报警包
    -   `composer require hyperf-admin/rule-engine` 规则引擎
    -   `composer require hyperf-admin/event-bus` mq/nsq/kafka消息派发器
    -   `composer require hyperf-admin/process-manager` 进程管理组件
-   业务组件 (业务组件为包含特定业务功能的包)
    -   `composer require hyperf-admin/admin` 系统管理业务包
    -   `composer require hyperf-admin/dev-tools` 开发者工具包, 主要是代码生成, 辅助开发
    -   `composer require hyperf-admin/cron-center` 定时任务管理, 后台化管理任务
    -   `composer require hyperf-admin/data-focus` 数据面板模块, 帮你快速制作数据大盘

## 前端的安装

```shell
# 环境依赖
# 1.  node ^v11.2.0 https://nodejs.org/zh-cn/download/
# 2.  npm ^6.4.1
git clone https://github.com/hyperf-admin/hyperf-admin-frontend.git
cd hyperf-admin-frontend
npm i
npm run dev
```

请根据实际情况修改`vue.config.js`中的代理 `proxy.target`地址

```shell
# 打包
npm run build:prod
npm run build:test
```

## 后端的安装

#### 1. 初始化一个`hypef`项目
```shell
# 环境依赖 php ^7.2 composer swoole 
composer create-project hyperf/hyperf-skeleton hyperf-admin
cd hyperf-admin
``` 

#### 2. 移除`hyperf-skeleton`中的日志配置, 因为 `admin` 底层已配置
```shell
rm config/autoload/logger.php
```

#### 3. 安装`hyperf-admin`的依赖DB信息

hyperf-admin 为分包的模式, 此处引入的是完整仓库, 实际项目请按需引入

全部的`mysql` 表结构及及基础数据详见 [demo/db](https://github.com/hyperf-admin/hyperf-admin-demo/tree/master/docker/db)

#### 4. 修改项目`.env`
```shell
APP_NAME=hyperf-admin
ENV=dev

# Redis链接信息
REDIS_HOST=localhost
REDIS_AUTH=(null)
REDIS_PORT=6379
REDIS_DB=0

# hyperf-admin 依赖的核心db
HYPERF_ADMIN_DB_HOST=localhost
HYPERF_ADMIN_DB_PORT=3306
HYPERF_ADMIN_DB_NAME=hyperf_admin
HYPERF_ADMIN_DB_USER=root
HYPERF_ADMIN_DB_PWD=root

LOCAL_DB_HOST=localhost
```

#### 5. 安装`hyperf-admin`扩展包
```shell 
composer require hyperf-admin/hyperf-admin
```
hyperf-admin 为分包模式, 实际应用中请根据情况安装

#### 6. 初始化`validation`的依赖文档
```shell
php bin/hyperf.php vendor:publish hyperf/translation
php bin/hyperf.php vendor:publish hyperf/validation
```

#### 7. 设置用户密码的加密`key`, 配置节点`password.salt`
```shell
// config/config.php

'password' => [
    'salt' => env('HYPERF_ADMIN_PWD_SALT', 'c093d70f088499c3a837cae00c042f14'), // 用 md5(time()) 获取 salt
```

#### 8. 启动
```shell
# 启动 热重启参考 https://github.com/daodao97/hyperf-watch
composer watch
```

## nginx配置

```nginx
upstream backend {
    server 127.0.0.1:9511;
}

server {
    listen 80;
    server_name hyperf-admin.com; # 设置自己的 domain
    index index.html;
    root /opt/www/hyperf-admin-front/dist;
    access_log /usr/local/var/log/nginx/hyperf-admin.access.log;
    error_log /usr/local/var/log/nginx/hyperf-admin.error.log;

    location ~ /api/(.*) {
        proxy_http_version 1.1;
        proxy_set_header Connection "keep-alive";
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header Host hyperf-admin.com;
        proxy_pass http://backend/$1$is_args$args;
    }

    location / {
        root /opt/www/hyperf-admin-front/dist/default;
        index index.html;
    }

    location ~ /(.*) {
        set $module $1;
        if ($module ~* '^$') {
            set $module default;
        }
        try_files $uri $uri/ /$module/index.html;
    }
}
```

浏览器打开 [http://youdomain.com:8081/default/#/dashboard](http://youdomain.com:8081/default/#/dashboard) 即可访问

默认账号 `daodao`, 密码 `a1a1a1`
