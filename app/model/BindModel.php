<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2019/3/1 19:08
 */
class BindModel extends Model
{
    protected array $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'type' => ['text'],
        'bind' => ['text', 'not null'],
        'token' => ['text', 'not null'],
        'expire' => ['text']
    ];
    protected array $_pk = ['id'];
    protected string $_ai = 'id';

    public function getUid($bind_uid, $type)
    {
        if ($bind_row = $this->where(['bind=:b and type=:t'], ['b' => $bind_uid, 't' => $type])->fetch()) {
            return $bind_row['uid'];
        } else {
            return null;
        }
    }
}