?> `hyperf-admin`目前尚未开源, 敬请期待.

## 前端

```shell
# 环境依赖
# 1.  node ^v11.2.0 https://nodejs.org/zh-cn/download/
# 2.  npm ^6.4.1
git clone https://github.com/hyperf-admin/hyperf-admin-front.git
cd hyperf-admin-front
npm i
npm run dev
```

!> 请根据实际情况修改`vue.config.js`中的代理 `proxy.target`地址

```shell
# 打包
npm run build:prod
npm run build:test
```

## 后端

```shell
# 环境依赖 php ^7.1 composer swoole 
# 下载demo项目
git clone https://github.com/hyperf-admin/hyperf-admin-skeleton.git
cd hyperf-admin-skeleton
composer i
# 启动
composer watch
```

## nginx配置

```nginx
# conf 
```
