### 为什么要做 CronCenter

#### 当前定时任务管理的解放方案及问题
1. 系统原生 crontab
   - 需要有服务器权限, 有改动都要登录服务器手动操作
2. hyperf/crontab 提供的 基于 swoole/timer 的模拟 crontab
   - 定时任务的编排以硬编码的方式放置在配置文件中
   - 如果有变动需要 `编码->改配置->CodeReview->部署` 走一遍完整的上限流程
3. AirFlow 等工具
   - 诚然也是一种很优秀的解决方案, 但对轻量级的定时任务需求来说有显得过重
    
### 如何规避上面的问题?

`CronCenter` 的思路是, 在 `hyperf/crontab` 的基础上, 将 `config/autoload/crontab.php` 中的定时任务配置, 迁移到数据库中, 提供后台, 以方便的管理所有定时任务的 `启动`, `停止`, `运行参数`, `单例/多例` 等等, 这样将极大的方便我们对定时任务的管理, 也相对轻量.

#### 具体实现

`hyperf-admin/cron-center` 重载了 `hyperf/crontab` 的关键类 `Hyperf/Crontab/Strategy/Executor`, 具体源码 [cron-center](https://github.com/hyperf-admin/hyperf-admin/blob/master/src/cron-center/src/ConfigProvider.php#L31)

#### 后台概览

![list](https://gitee.com/daodao97/asset/raw/master/imgs/8ynybw.png)
![lQJf50](https://gitee.com/daodao97/asset/raw/master/imgs/lQJf50.png)

#### 使用细节

脚本作业的管理中心, 可以在代码中实现`class`类型, `command`类型的脚本作业, 在后台添加相关任务, 即可在相应脚本机上执行

可以对任务的入口, 执行规划, 执行节点, 执行参数 等进行配置, 也可在列表主动触发任务

作业必须基于`HyperfAdmin/CronCenter/ClassJobAbstract`, 或`HyperfAdmin/CronCenter/CommandJobAbstract.php`抽象类进行实现, 才可进行执行状态的跟踪

### 相关配置

1. 首先要启用 `hyperf/crotnab`
`config/autoload/crontab.php`
```php
// config/autoload/crontab.php
return [
    "enable" => true,
    ...
]
```
2. 启用 `CronCenter`
```php
// config/config.php
return [
    "cron_center" => [
        "enable" => true,
    ],
    ...
]
```

### 此方案的问题

此种模拟式的 `crontab` 有一个很明显的弊端, 也就是任务的 `派遣`, `运行` 等都依赖于服务进程, 也就是服务一旦重启, 运行中的任务都会停止, 如果对任务的运行中断极度敏感, 则不建议使用此种方式.
