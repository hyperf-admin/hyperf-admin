`hyperf-admin`是前后端分离的后台管理系统, 前端基于`vue`的 `vue-admin-template`, 针对后台业务`列表`, `表单`等场景封装了大量业务组件, 后端基于`hyperf`实现, 整体思路是后端定义页面渲染规则, 前端页面渲染时首先拉取配置, 然后组件根据具体配置完成页面渲染, 方便开发者仅做少量的配置工作就能完成常见的`CRUD`工作, 同时支持自定义组件和自定义页面, 以开发更为复杂的页面.

![hyperf-admin架构](https://cdn.jsdelivr.net/gh/daodao97/FigureBed@master/uPic/sJaJti.png)

前端为`vue multiple page`多页模式, 可以按模块打包, 默认包含两个模块`default` 默认模块,  `system`系统管理模块,  绝大部分业务组件在`src/components`目录, 前端文档详见 [这里](/frontend/form)

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

后端的详细文档见[这里](/backend/scaffold)

## 依赖 & 参考

-   前端
    -   [Vue](https://github.com/vuejs/vue)
    -   [ElementUI](https://github.com/ElemeFE/element)
    -   [FormCreate](http://www.form-create.com/v2/guide)
    -   [vue-admin-template](https://github.com/PanJiaChen/vue-admin-template)
    -   [Vue 渲染函数 & JSX](https://cn.vuejs.org/v2/guide/render-function.html)
-   后端
    -   [Hyperf](http://hyperf.wiki/)
    -   [Swoole](http://wiki.swoole.com)
