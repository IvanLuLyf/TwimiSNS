# TwimiSNS

![TwimiSNS](static/img/logo.png?raw=true)

TwimiSNS是一个基于BunnyPHP开发的SNS引擎.

![GitHub release](https://img.shields.io/github/release/ivanlulyf/twimisns.svg?color=brightgreen&style=flat-square)
![Code Size](https://img.shields.io/github/languages/code-size/ivanlulyf/mineblog.svg?color=orange&style=flat-square)
![GitHub](https://img.shields.io/github/license/ivanlulyf/twimisns.svg?color=blue&style=flat-square)
![PHP](https://img.shields.io/badge/PHP->%3D7.0.0-777bb3.svg?style=flat-square&logo=php)

[English](README.md) | 中文

## 环境要求

* PHP >= 7.0
* MySQL 或 SQLite


## 安装

### 1. 复制本项目到网站根目录

### 2. 配置服务器环境
> Apache

添加以下内容到 ```.htacess``` 文件中.

```apacheconfig
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . index.php
</IfModule>
```


> Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
```

### 3. 打开 http://yourdomain/install 根据提示完成安装

## 错误码表

|  Code  | Description |
|:---:|---|
|0|ok|
|-1|网络错误|
|-2|Mod不存在|
|-3|Action不存在|
|-4|模板不存在|
|-5|模板渲染错误|
|-6|数据库错误|
|-7|参数不可为空|
|-8|内部错误|
|1|非法的csrf token|
|2|无效的文件|
|1001|密码错误|
|1002|用户不存在|
|1003|用户名已存在|
|1004|无效的用户名|
|1005|不允许注册|
|1006|无效的id code|
|1007|站点未开启OAuth|
|1008|无效的验证码|
|2001|无效的client id|
|2002|没有权限|
|2003|无效的token|
|2004|无效的oauth code|
|2005|无效的refresh token|
|3001|无效的tid|
|3002|没有权限|
|3003|已赞|
|4001|用户不是好友|
|4002|用户已是好友|
|5001|错误的支付密码|
|5002|已支付|
|5003|余额不足|
|5004|不需要支付|
|5005|红包为空|
|5006|无效的金额|