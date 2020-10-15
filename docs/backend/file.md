## 文件上传

文件的处理统一使用 `hyperf/filesystem`, 请先阅读其文档 [biu~~](https://hyperf.wiki/2.0/#/zh-cn/filesystem)

### 文件的上传

表单的控件中可以指定 `存储介质`, `可见性` 等.

```php
 'form' => [
    'avatar|用户头像' => [
        'type' => 'image',
        'rule' => 'string',
        'readonly' => true,
        'props' => [
            'bucket' => 'aliyuncs', // 指定存储的storage, 可选详见 config/autoload/file.php storage
            'private' => true, // 是否为私有
        ]
    ],
]
```

### 两个快捷方法

1. `move_local_file_to_filesystem($local_file_path, $save_file_path, $private = false, $bucket = 'aliyuncs', $update_when_exist = true)`
    将本地文件通过 `filesystem` 指定的介质来存储
2. `filesystem_private_url($save_file_path, $timeout = 60, $bucket = 'aliyuncs')`
    获取私有文件的临时访问链接
    
### 提示

如果存储方式为 `本地`, 我们 增加了 `file.storage.local.cdn` 这个配置项, 用于生成可用的访问链接.

如果使用的其他存储介质, 请记得安装相应扩展包.

!> 当前仅对 `本地`, `阿里云oss` 做了适配, 因为没有其他元的账号..., 如果您当前使用的存储介质(如: 腾讯云), 请反馈给我们, 如能提供测试账号验证下更好.

