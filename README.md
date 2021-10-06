# TwimiSNS

![TwimiSNS](static/img/logo.png?raw=true)

TwimiSNS is a SNS Engine Powered By BunnyPHP.

![GitHub release](https://img.shields.io/github/release/ivanlulyf/twimisns.svg?color=brightgreen&style=flat-square)
![Code Size](https://img.shields.io/github/languages/code-size/ivanlulyf/twimisns.svg?color=orange&style=flat-square)
![GitHub](https://img.shields.io/github/license/ivanlulyf/twimisns.svg?color=blue&style=flat-square)
![PHP](https://img.shields.io/badge/PHP->%3D7.4.0-777bb3.svg?style=flat-square&logo=php)

[![Deploy to Heroku](https://img.shields.io/badge/-Deploy%20to%20Heroku-%237056BF?logo=heroku&style=flat-square&labelColor=%237056BF&logoColor=white)](https://heroku.com/deploy?template=https://github.com/IvanLuLyf/TwimiSNS)
[![Run on Repl.it](https://img.shields.io/badge/-Run%20on%20Repl.it-%235C6970?logo=replit&style=flat-square&logoColor=white)](https://repl.it/github/ivanlulyf/twimisns)
[![Deploy with Vercel](https://img.shields.io/badge/-Deploy%20with%20Vercel-%231374EF?logo=vercel&style=flat-square&labelColor=%231374EF&logoColor=white)](https://vercel.com/new/git/external?repository-url=https://github.com/IvanLuLyf/TwimiSNS&project-name=twimi-sns&repository-name=twimi-sns)

English | [中文](README_CN.md)

## Requirement

* PHP >= 7.4
* PostgreSQL, MySQL or SQLite


## Installation

### 1. Clone this repository to your site root.

### 2. Set up your server
#### Apache

Add following content to ```.htacess``` file.

```apacheconfig
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . index.php
</IfModule>
```


#### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
```

### 3. Open http://yourdomain/install

## Return Code Reference

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
