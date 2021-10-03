<?php

use BunnyPHP\Model;

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/3/5
 * Time: 23:47
 */
class PostPayModel extends Model
{
    protected $_column = [
        'id' => ['integer', 'not null'],
        'tid' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
    ];
    protected $_pk = ['id'];
    protected $_ai = 'id';

    public function check($uid, $tid)
    {
        return $this->where("tid=:t and uid=:u", ['u' => $uid, 't' => $tid])->fetch() != null;
    }

    public function pay($uid, $tid)
    {
        return $this->add(['uid' => $uid, 'tid' => $tid]);
    }
}