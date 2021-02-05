?> 如果仅是体验该项目, 请访问演示站点[hyperf-admin](http://hyperf-admin.daodao.run/) 

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

!> 请根据实际情况修改`vue.config.js`中的代理 `proxy.target`地址

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

底层的日志配置见 [base-utils/ConfigProvider](https://github.com/hyperf-admin/hyperf-admin/blob/master/src/base-utils/src/ConfigProvider.php#L22)
```shell
rm config/autoload/logger.php
```

#### 3. 安装`hyperf-admin`的依赖DB信息

!> hyperf-admin 为分包的模式, 此处引入的是完整仓库, 实际项目请按需引入

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

如果存在依赖包的版本号问题, 注意, 请使用 composer2
```shell
composer require hyperf-admin/hyperf-admin:0.2.0 --with-all-dependencies
```

!> hyperf-admin 为分包模式, 实际应用中请根据情况安装

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
 ]
```

#### 8. 启动
```shell
# 启动 热重启参考 https://github.com/daodao97/hyperf-watch
composer watch
```

## 生产环境nginx配置

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
