脚本作业的管理中心, 可以在代码中实现`class`类型, `command`类型的脚本作业, 在后台添加相关任务, 即可在相应脚本机上执行

可以对任务的入口, 执行规划, 执行节点, 执行参数 等进行配置, 也可在列表主动触发任务

作业必须基于`App\Util\CronCenter\ClassJobAbstract`, 或`App/Util/CronCenter/CommandJobAbstract.php`抽象类进行实现, 才可进行执行状态的跟踪

`CronCenter`的实现基于`hyperf-crontab`进行实现, 具体代码在`app/Util/CronCenter`, 更多细节可查看[文档](https://hyperf.wiki/#/zh-cn/crontab)
