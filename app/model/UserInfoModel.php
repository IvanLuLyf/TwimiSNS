<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2019/2/28 23:17
 */
class UserInfoModel extends Model
{
    protected array $_column = [
        'uid' => ['integer', 'not null'],
        'signature' => ['text'],
        'cover' => ['text'],
        'background' => ['text'],
    ];
    protected array $_pk = ['uid'];

    public function get($uid)
    {
        return $this->where(['uid = :uid'], ['uid' => intval($uid)])->fetch();
    }
}