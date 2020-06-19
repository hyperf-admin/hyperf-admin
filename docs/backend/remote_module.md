绝大部分的前后端分离的后台构建中, 一般都是`一个前端`->`一个后端`的架构, 但是随着业务的发展会有以下现象

1.  后端代码越来越庞大
2.  代码中各种 `Consumer`, `Process`
3.  过多的 `Worker + Process`会占用大量的机器资源, 单机内存,CPU有时告警的风险
4.  服务职能不纯粹
5.  等等等等

所以我们的服务一步步的变得膨胀, 变得不稳定, 是时候做出改变了

![Biv9dV](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/Biv9dV.png)

我们将一些业务边界比较明显, 比较独立的功能, 以微服务的方式拆分出去, 然后注册到主项目上去, 这样我们就可以达到, 既使用一套ui, 又进行服务拆分的目的啦.

`remote_module`的注册也十分简单, `http://localhost:9528/system/#/cconf/cconf_website_config` 在站点管理中增加相应模块的配置即可

![1DIbj0](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/1DIbj0.png)

然后在`菜单管理`中, 增加相应的模块菜单即可使用啦.

?> 注意 `remote_module` 必须基于`hyperf-admin/admin` 组件构建哦

?> 另外, 主的`HyperfAdmin`项目, 默认包含两个本地模块, `default`, `system`