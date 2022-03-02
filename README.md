## yapi-sdk 为Yapi平台写的SDK

## 安装
> composer require fuyelk/yapi-sdk

> Yapi官方地址   
> https://github.com/YMFE/yapi

## 特性
1. 自动登录
2. 支持导出4种文件类型

## 方法
```
$email = 'admin@admin.com';
$password = '123456';
$host = 'http://api.example.com:3000';
$yapi = new Yapi($email, $password, $host);

// 导出html
$res = $yapi->export(100, 'html', __DIR__ . '/temp/export.html');

// 导出JSON文件
$res = $yapi->export(100, 'json', __DIR__ . '/temp/export.json');

// 导出markdown
$res = $yapi->export(100, 'markdown', __DIR__ . '/temp/export.markdown');

// 导出swagger
$res = $yapi->export(100, 'swagger', __DIR__ . '/temp/export.swagger');
```