#### 1. 导出服务的进度一直是0

导出任务没有默认启动，有两种方式可以启用他
1. 使用croncenter 的话，在 定时任务的管理后台 增加 export-task 任务即可, 参见[cron-center](http://daodao97.gitee.io/hyperf-admin/#/backend/components/business/cron-center)
2. 未使用croncenter 自己起个进程或定时任务 启动 `HyperfAdmin/Admin/Service/ExportService` 即可
  
#### 2. 前端页面点击菜单, 不能正常跳转
打开控制台可见如下错

![X6ijLg](https://gitee.com/daodao97/asset/raw/master/imgs/X6ijLg.png)

此时, 保证本地的 `node`, `npm` 为最新版本, 安装依赖时, 不要使用 `cnpm`

然后 `npm i` 或 `yarn` 重新安装并启动

#### 3. 点击下载中心的文件链接跳转地址提示需要登录

这里的逻辑是先跳转到 http://hyperf-admin.daodao.run/api/upload/ossprivateurl?key=oss/1/export_task/xxxxx.csv, 由接口 `api/upload/ossprivateurl` 生成临时的下载地址, 也就是我们需要此接口的权限, 如下操作即可 
![KwtN0f](https://gitee.com/daodao97/asset/raw/master/imgs/KwtN0f.png)
勾选接口后, 点击提交, 并清理权限缓存
![EsMcs5](https://gitee.com/daodao97/asset/raw/master/imgs/EsMcs5.png)
同理, 其他接口的权限问题也可在此管理
