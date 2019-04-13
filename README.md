# TwimiSNS

![TwimiSNS](static/img/logo.png?raw=true)

TwimiSNS is a SNS Engine Powered By BunnyPHP

![GitHub release](https://img.shields.io/github/release/ivanlulyf/twimisns.svg?color=brightgreen)
![GitHub](https://img.shields.io/github/license/ivanlulyf/twimisns.svg?color=blue)

## Requirement

* PHP >= 7.0
* MySQL or SQLite


## Installation

### 1. Clone this repository to your site root.

### 2. Set up your server
> Apache

Add following content to ```.htacess``` file.

```
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . index.php
</IfModule>
```


> Nginx

```
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
```

### 3. Open http://yourdomain/install

## Return Code Reference

|  Code  | Description |
|------- |-------------|
|0       |ok           |
|1001    |password error|
|1002    |user not exists|
|1003    |username exists|
|1004    |empty arguments|
|1005    |invalid username|
|1006    |database error|
|1007    |register not allowed|
|1008    |invalid id code|
|1009    |already exist|
|1010    |oauth is not enabled|
|2001    |invalid client id|
|2002    |permission denied|
|2003    |invalid token|
|2004    |invalid crsf token|
|2005    |invalid oauth code|
|2006    |invalid refresh token|
|3001    |invalid tid|
|3002    |already liked|
|3003    |invalid action|
|4001    |no friend|
|5001    |invalid password|
|5002    |already pay|
|5003    |no enough coin|
|5004    |no need to pay|
|5005    |empty red packet|
|5006    |invalid price|