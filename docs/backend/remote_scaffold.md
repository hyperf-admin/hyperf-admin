远程脚手架配置 与 [远程模块](https://hyperf-admin.github.io/hyperf-admin/#/backend/remote_module) 的作用类似, 但又有不同, 远程模块的作用是 为 `hyperf-admin` 接入另外一个用`hyperf-admin` 实现的项目, 但在实际开发中, 后台页面可能需要为其他业务团队搭建页面, 而对于后端的实现可能 不是 `hyperf-admin` 甚至不是 `php`, 这时我们可以使用 `远程脚手架的模式` 为第三方业务提供后台页面的管理功能, 三方业务仅用实现对应 `api` 即可

我们以 `用户管理` 这个页面功能为例, 讲解如何使用此功能.

先上效果图
![MXURtd](https://gitee.com/daodao97/asset/raw/master/uPic/MXURtd.png)

![rs0BhV](https://gitee.com/daodao97/asset/raw/master/uPic/rs0BhV.png)

此时我们只用配置好脚手架配置,即可为第三方服务提供后台页面的管理功能
```json
{
  "getApi": "http://127.0.0.1:9511/user/{id}",
  "saveApi": "http://127.0.0.1:9511/user/{id}",
  "listApi": "http://127.0.0.1:9511/user/list",
  "createAble": false,
  "deleteAble": true,
  "defaultList": true,
  "filter": [
    "realname%",
    "username%",
    "create_at"
  ],
  "form": {
    "id": "int",
    "username|登录账号": {
      "rule": "required",
      "readonly": true
    },
    "avatar|头像": {
      "type": "image",
      "rule": "string"
    },
    "realname|昵称": "",
    "mobile|手机": "",
    "email|邮箱": "email",
    "sign|签名": "",
    "pwd|密码": {
      "default": "",
      "props": {
        "size": "small",
        "maxlength": 20
      },
      "info": "若设置, 将会更新用户密码"
    },
    "status|状态": {
      "rule": "required",
      "type": "radio",
      "options": [
        "禁用",
        "启用"
      ],
      "default": 0
    },
    "is_admin|类型": {
      "rule": "int",
      "type": "radio",
      "options": [
        "普通管理员",
        "超级管理员"
      ],
      "info": "普通管理员需要分配角色才能访问角色对应的资源；超级管理员可以访问全部资源",
      "default": 0
    },
    "role_ids|角色": {
      "rule": "array",
      "type": "el-cascader-panel",
      "props": {
        "props": {
          "multiple": true,
          "leaf": "leaf",
          "emitPath": false,
          "checkStrictly": true
        }
      }
    },
    "create_at|创建时间": {
      "form": false,
      "type": "date_range"
    }
  },
  "hasOne": [],
  "table": {
    "columns": [
      "id",
      "realname",
      "username",
      {
        "field": "mobile"
      },
      {
        "field": "avatar",
        "render": "avatarRender"
      },
      "email",
      {
        "field": "status",
        "enum": [
          "info",
          "success"
        ]
      },
      {
        "field": "role_id",
        "title": "权限"
      }
    ],
    "rowActions": [
      {
        "type": "form",
        "target": "/system/form/89/{id}",
        "text": "编辑"
      },
      {
        "type": "form",
        "target": "_proxy@http://127.0.0.1:9511/user/form",
        "text": "编辑2"
      }
    ],
    "topActions": [
      {
        "type": "form",
        "target": "/system/form/89/form",
        "text": "新建"
      }
    ]
  }
}
```

熟系 [脚手架](https://hyperf-admin.github.io/hyperf-admin/#/backend/scaffold)这节的朋友会发现, 这个配置和 脚手架中 `public function scaffoldOptions()` 方法的配置几乎是一样的, 是的, 当前这个功能把 `脚手架配置` 抽离到的数据库中, 不在依赖代码生成, 极大的提高了易用性. 

有几个特殊的配置项
- `getApi` 当前管理对象单一对象的获取接口, form表单复现数据时使用
- `saveApi` 当前管理对象单一对象保存接口, form表单提交时使用
- `listApi` 当前对象的列表数据接口

按钮的 `target` 里还有如下写法 `_proxy@http://127.0.0.1:9511/user/form`, 操作按钮后 系统会自动重写到 `/system/proxy?proxy_url=http%3A%2F%2F127.0.0.1%3A9511%2Fuser%2Fform` 接口, 又 `hyperf-admin` 后端代码 完成代理请求.

此功能的具体实现代码在 `src/admin/src/Controller/SystemController.php` 感兴趣的朋友可以看下源码, 十分简单.

