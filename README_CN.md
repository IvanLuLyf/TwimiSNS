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
|-1|network error|
|-2|mod does not exist|
|-3|action does not exist|
|-4|template does not exist|
|-5|template rendering error|
|-6|database error|
|-7|parameter cannot be empty|
|-8|internal error|
|1|invalid csrf token|
|2|invalid file|
|1001|wrong password|
|1002|user does not exist|
|1003|username already exists|
|1004|invalid username|
|1005|registration is not allowed|
|1006|invalid id code|
|1007|oauth is not enabled|
|1008|invalid verification code|
|2001|invalid client id|
|2002|permission denied|
|2003|invalid token|
|2004|invalid oauth code|
|2005|invalid refresh token|
|3001|invalid tid|
|3002|permission denied|
|3003|already liked|
|4001|user is not a friend|
|4002|user is already a friend|
|5001|wrong payment password|
|5002|already paid|
|5003|insufficient balance|
|5004|no need to pay|
|5005|empty red packet|
|5006|invalid amount|