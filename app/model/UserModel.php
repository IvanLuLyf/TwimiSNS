<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2018/7/29 1:27
 */
class UserModel extends Model
{
    protected array $_column = [
        'uid' => ['integer', 'not null'],
        'username' => ['varchar(16)', 'not null'],
        'password' => ['varchar(32)', 'not null'],
        'nickname' => ['varchar(32)'],
        'email' => ['text', 'not null'],
        'avatar' => ['text'],
        'token' => ['text', 'not null'],
        'expire' => ['bigint']
    ];
    protected array $_pk = ['uid'];
    protected string $_ai = 'uid';
    protected static array $NO_SENSITIVE_FIELD = ['uid', 'username', 'nickname', 'avatar'];

    public function getUsers($page = 1)
    {
        return $this->limit(10, ($page - 1) * 10)->fetchAll();
    }

    public function refresh($uid)
    {
        $user = $this->where('uid = :u', ['u' => $uid])->fetch();
        $timestamp = time();
        if ($user['expire'] == null || $timestamp > intval($user['expire'])) {
            $token = $this->createToken($user['username'], $timestamp);
            $updates = ['token' => $token, 'expire' => $timestamp + 604800];
            $this->where(["uid = :uid"], ['uid' => $uid])->update($updates);
        } else {
            $token = $user['token'];
        }
        return $token;
    }

    public function reset($uid, $password)
    {
        return $this->where('uid = :u', ['u' => $uid])->update(['password' => $this->encodePassword($password)]);
    }

    public function login(string $username, string $password): array
    {
        $user = $this->where('username = :u or email = :e', ['u' => $username, 'e' => $username])->fetch();
        if (!$user) return ['ret' => 1002, 'status' => 'user does not exist', 'tp_error_msg' => '用户名不存在'];
        if ($user['password'] != $this->encodePassword($password)) {
            return ['ret' => 1001, 'status' => 'wrong password', 'tp_error_msg' => '密码错误'];
        }
        $timestamp = time();
        $uid = $user['uid'];
        if (empty($user['expire']) || $timestamp > intval($user['expire'])) {
            $token = $this->createToken($user['username'], $timestamp);
            $expire = $timestamp + 604800;
            $updates = ['token' => $token, 'expire' => $expire];
            $this->where(["uid = :uid"], ['uid' => $uid])->update($updates);
        } else {
            $token = $user['token'];
            $expire = $user['expire'];
        }
        return ['ret' => 0, 'status' => 'ok', 'uid' => $uid, 'username' => $user['username'], 'email' => $user['email'], 'token' => $token, 'nickname' => $user['nickname'], 'expire' => $expire];
    }

    public function register($username, $password, $email, $nickname = ''): array
    {
        if (!isset($username) || !isset($password) || !isset($email)) {
            return ['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '参数不能为空'];
        }
        if (!$this->validateUsername($username)) {
            return ['ret' => 1004, 'status' => 'invalid username', 'tp_error_msg' => '用户名仅能为字母数字且长度大于4'];
        }
        if (!$this->validateEmail($email)) {
            return ['ret' => 1004, 'status' => 'invalid email', 'tp_error_msg' => '邮箱格式错误'];
        }
        if ($this->where("username = :u or email = :e", ['u' => $username, 'e' => $email])->fetch()) {
            return ['ret' => 1003, 'status' => 'username already exists', 'tp_error_msg' => '用户名已存在'];
        }
        if ($nickname == '') {
            $nickname = $username;
        }
        $timestamp = time();
        $token = $this->createToken($username, $timestamp);
        $new_data = [
            'username' => $username,
            'email' => $email,
            'password' => $this->encodePassword($password),
            'nickname' => $nickname,
            'token' => $token,
            'expire' => $timestamp + 604800
        ];
        if ($uid = $this->add($new_data)) {
            return ['ret' => 0, 'status' => 'ok', 'uid' => $uid, 'username' => $username, 'email' => $email, 'token' => $token, 'nickname' => $nickname];
        } else {
            return ['ret' => -6, 'status' => "database error", 'tp_error_msg' => '数据库内部出错'];
        }
    }

    public function check($token)
    {
        return $this->where('token = ? and expire> ?', [$token, time()])->fetch();
    }

    public function getUserByUid($uid, $field = null): array
    {
        return $this->where('uid = ?', [$uid])->fetch($field ?: self::$NO_SENSITIVE_FIELD);
    }

    public function getUserByUsername($username, $field = null): array
    {
        return $this->where('username = ?', [$username])->fetch($field ?: self::$NO_SENSITIVE_FIELD);
    }

    public function getTokenByUid($uid)
    {
        if ($user = $this->where('uid = ?', [$uid])->fetch()) {
            return $user['token'];
        } else {
            return null;
        }
    }

    public function updateAvatar($uid, $url)
    {
        return $this->where(['uid = :uid'], ['uid' => $uid])->update(['avatar' => $url]);
    }

    public function getAvatar($user, $isId = false)
    {
        $default = '/static/img/avatar.png';
        if ($row = $this->where($isId ? 'uid=?' : 'username=?', [$user])->fetch('avatar')) {
            return $row['avatar'] ?: $default;
        } else {
            return $default;
        }
    }

    private function validateUsername($username): bool
    {
        return preg_match('/^[A-Za-z0-9_]+$/u', $username) && strlen($username) >= 4;
    }

    private function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function encodePassword($password): string
    {
        return md5($password);
    }

    private function createToken($username, $timestamp): string
    {
        return md5($username . $timestamp);
    }
}