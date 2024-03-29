<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2019/3/5 23:47
 */
class PostPayModel extends Model
{
    protected array $_column = [
        'id' => ['integer', 'not null'],
        'tid' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
    ];
    protected array $_pk = ['id'];
    protected string $_ai = 'id';

    public function check($uid, $tid)
    {
        if (is_array($tid)) {
            $rows = $this->where('uid=? and tid in(' . StrUtil::sqlIn($tid) . ')', array_merge([$uid], $tid))->fetchAll('tid');
            return array_map('tid', $rows);
        } else {
            return $this->where('tid=:t and uid=:u', ['u' => $uid, 't' => $tid])->fetch() != null;
        }
    }

    public function pay($uid, $tid)
    {
        return $this->add(['uid' => $uid, 'tid' => $tid]);
    }
}