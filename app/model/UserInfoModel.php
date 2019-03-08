<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/2/28
 * Time: 23:17
 */

class UserInfoModel extends Model
{
    protected $_column = [
        'uid' => ['integer', 'not null'],
        'signature' => ['text'],
        'cover' => ['text'],
        'background' => ['text'],
    ];
    protected $_pk = ['uid'];

    public function get($uid)
    {
        return $this->where(['uid = :uid'], ['uid' => intval($uid)])->fetch();
    }
}