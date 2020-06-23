`hyperf-admin` 的运行 demo, [仓库地址](https://github.com/hyperf-admin/hyperf-admin-demo)

### 1. 一键启动
先确认 `docker`, `docker-compose` 已安装

```bash
git clone https://github.com/hyperf-admin/hyperf-admin-demo.git
cd hyperf-admin-demo 
docker-compose up
```

浏览器打开 `http://127.0.0.1:8081/default/#/dashboard` 即可访问

默认账号 ` daodao`, 密码 `a1a1a1`

### 2. 无 docker 启动

首先将 `docker/db` 下的 `sql` 文件添加的本地 `mysql` 中

然后

```bash
git clone https://github.com/hyperf-admin/hyperf-admin-demo.git
cd hyperf-admin-demo/backend
composer i
```

再然后将 `docker/conf.d/hyperf-admin.conf` 拷贝到本地 `nginx` 目录, 注意, 记得添加 `server_name`

此时访问本地的 `server_name` 即可.
