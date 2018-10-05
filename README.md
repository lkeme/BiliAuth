# Bili-Auth
(BILIBILI) B站登陆API盒子

## 环境依赖
PHP 5.6+ Curl, OpenSSL extension installed.
> **Note:** BiliAuth requires [cURL](http://php.net/manual/en/book.curl.php) and [OpenSSL](http://php.net/manual/en/book.openssl.php) extension in order to work.

## 安装程序
需要安装: [Composer](https://getcomposer.org)

```bash
$ composer require lkeme/bili-auth
```

## 快速开始
在你的项目中引用代码:

```php
use Lkeme\BiliAuth;

$api = new BiliAuth();

$data = $api->login('username','password');
```

## 所有方法

### 登陆
```php
$api->login('username', 'password');
```

|参数名|必须|描述|
| ------ |-------|-----------|
|username|√      |哔哩哔哩账号|
|password|√      |哔哩哔哩密码|

```json
{
	"code": 0,
	"data": {
		"uid": 123456,
		"userName": "123456",
		"accessToken": "81dc9bdb52d04dc20036dbd8313ed055",
		"refreshToken": "202cb962ac59075b964b07152d234b70",
		"cookieInfo": "bili_jct=...;DedeUserID=...;DedeUserID__ckMd5=...;sid=...;SESSDATA=...;",
		"csrfToken": "827ccb0eea8a706c4c34a16891f84e7b",
		"expires_in": "2018-11-11 11:11:11"
	},
	"message": "账号登陆成功!"
}
```

### 验证码登陆
> 需要先获取验证码
```php
$api->login('username', 'password', 'captcha', 'cookie');
```

|参数名|必须|描述|
| ------ |-------|-----------|
|username|√      |哔哩哔哩账号|
|password|√      |哔哩哔哩密码|
|captcha |√      |验证码      |
|cookie  |√      |对应验证码  |

```json
{
	"code": 0,
	"data": {
		"uid": 123456,
		"userName": "123456",
		"accessToken": "81dc9bdb52d04dc20036dbd8313ed055",
		"refreshToken": "202cb962ac59075b964b07152d234b70",
		"cookieInfo": "bili_jct=...;DedeUserID=...;DedeUserID__ckMd5=...;sid=...;SESSDATA=...;",
		"csrfToken": "827ccb0eea8a706c4c34a16891f84e7b",
		"expires_in": "2018-11-11 11:11:11"
	},
	"message": "账号登陆成功!"
}
```

      	
### 获取验证码(验证码登陆前提)
```php
$api->getCapcha();
```

|参数名|必须|描述|
| ------ |-------|-----------|
|无      |无     |无          |

```json
{
	"code": 200,
	"cookie": "kxMAJX6f",
	"captcha_img": "base64图片",
	"bash64_head": "data:image/jpg/png/gif;base64,",
	"message": "获取验证码图片(Base64)!"
}
```


### 检测COOKIE有效性
```php
$api->checkCookie($cookie);
```

|参数名|必须|描述|
| ------ |-------|-----------|
|cookie  |√      |COOKIE     |

```json
{
	"code": 0,
	"status": "valid",
	"message": "检测 Cookie 有效!"
}
```

### 刷新令牌时效
```php
$api->refreshToken($access, $refresh);
```

|参数名|必须|描述|
| ------ |-------|-----------|
|access  |√      |ACCESS_TOEKN|
|refresh  |√     |REFRESH_TOEKN|

```json
{
	"code": 0,
	"data": {
		"mid": 123456,
		"refresh_token": "698d51a19d8a121ce581499d7b701668",
		"access_token": "b59c67bf196a4758191e42f76670ceba",
		"expires_in": "2018-11-11 11:11:11"
	},
	"message": "续签令牌: 成功!"
}
```

### 检测令牌有效性
```php
$api->checkToken($access);
```

|参数名|必须|描述|
| ------ |-------|-----------|
|access  |√      |ACCESS_TOEKN|

```json
{
	"code": 0,
	"status": 1,
	"message": "令牌验证成功，有效期至: 2018-11-11 11:11:11"
}
```

## 状态
B站的状态码很混乱，后面陆续记录一些
|状态码|描述|
|---  |-----------|
|0    |正常        |
|200  |CURL返回的  |
|-105  |登陆需要验证码|
|629  |账号密码错误|
|1024  |超时        |
|403  |IP问题       |


## TODO
- 待定


## Author

**Bili-Auth** © [lkeme](https://github.com/lkeme), Released under the [MIT](./LICENSE) License.<br>

> Blog [@lkeme](https://mudew.com) · GitHub [@lkeme](https://github.com/lkeme) 
