<?php

use BunnyPHP\Model;

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/3/1
 * Time: 19:08
 */
class BindModel extends Model
{
    protected $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'type' => ['text'],
        'bind' => ['text', 'not null'],
        'token' => ['text', 'not null'],
        'expire' => ['text']
    ];
    protected $_pk = ['id'];
    protected $_ai = 'id';

    public function getUid($bind_uid, $type)
    {
        if ($bind_row = $this->where(['bind=:b and type=:t'], ['b' => $bind_uid, 't' => $type])->fetch()) {
            return $bind_row['uid'];
        } else {
            return null;
        }
    }
}