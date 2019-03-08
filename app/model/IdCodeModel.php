<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/3/8
 * Time: 20:17
 */

class IdCodeModel extends Model
{
    protected $_column = [
        'uid' => ['integer', 'not null'],
        'code' => ['text', 'not null'],
    ];
    protected $_pk = ['uid'];

    public function getIdCode($uid)
    {
        if ($row = $this->where('uid=:u', ['u' => $uid])->fetch()) {
            return $row['code'];
        } else {
            $code = uniqid($uid % 10);
            $this->add(['uid' => $uid, 'code' => $code]);
            return $code;
        }
    }

    public function getIdByCode($code)
    {
        if ($row = $this->where('code=:c', ['c' => $code])) {
            return $row['uid'];
        } else {
            return 0;
        }
    }
}