<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2019/3/2 18:19
 */
class PayPassModel extends Model
{
    protected array $_column = [
        'uid' => ['integer', 'not null'],
        'password' => ['text', 'not null'],
    ];
    protected array $_pk = ['uid'];

    public function getPassword($uid)
    {
        if ($p = $this->where(['uid = :u'], ['u' => $uid])->fetch()) {
            return $p['password'];
        } else {
            return null;
        }
    }

    public function setPassword($uid, $password)
    {
        if ($p = $this->where(['uid = :u'], ['u' => $uid])->fetch()) {
            $this->where(['uid = :u'], ['u' => $uid])->update(['password' => $password]);
        } else {
            $this->add(['uid' => $uid, 'password' => $password]);
        }
    }
}