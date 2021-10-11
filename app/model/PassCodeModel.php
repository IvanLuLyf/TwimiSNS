<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2019/2/28 22:34
 */
class PassCodeModel extends Model
{
    protected array $_column = [
        'uid' => ['integer', 'not null'],
        'code' => ['text'],
        'expire' => ['integer']
    ];
    protected array $_pk = ['uid'];

    function getCode($uid)
    {
        if ($row = $this->where('uid=:u and expire>:t', ['u' => $uid, 't' => time()])->fetch()) {
            return $row['code'];
        } else {
            $code = md5("BunnyPHP" . time() . rand(1, 1000));
            $this->add(['uid' => $uid, 'code' => $code, 'expire' => time() + 1800]);
            return $code;
        }
    }

    function checkCode($code)
    {
        if ($row = $this->where('code=:c and expire>:t', ['c' => $code, 't' => time()])->fetch()) {
            $uid = $row['uid'];
            $this->where('uid=:u', ['u' => $uid])->delete();
            return $uid;
        } else {
            return null;
        }
    }
}