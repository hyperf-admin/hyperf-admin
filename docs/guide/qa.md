#### 1. 导出服务的进度一直是0

导出任务没有默认启动，有两种方式可以启用他
1. 使用croncenter 的话，在 定时任务的管理后台 增加 export-task 任务即可, 参见[cron-center](http://daodao97.gitee.io/hyperf-admin/#/backend/components/business/cron-center)
2. 未使用croncenter 自己起个进程或定时任务 启动 `HyperfAdmin/Admin/Service/ExportService` 即可
  
#### 2. 前端页面点击菜单, 不能正常跳转
打开控制台可见如下错

![X6ijLg](https://gitee.com/daodao97/asset/raw/master/imgs/X6ijLg.png)

此时, 保证本地的 `node`, `npm` 为最新版本, 安装依赖时, 不要使用 `cnpm`

然后 `npm i` 或 `yarn` 重新安装并启动

