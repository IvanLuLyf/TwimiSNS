<?php

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/1/8
 * Time: 13:29
 */
class OauthCodeModel extends Model
{
    protected $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'appid' => ['integer', 'not null'],
        'code' => ['text', 'not null'],
        'expire' => ['text']
    ];
    protected $_pk = ['id'];
    protected $_ai = 'id';

    public function getCode($clientId, $appId, $uid, $timestamp)
    {
        $code = md5($uid . $clientId . $timestamp);
        $this->add(['uid' => $uid, 'appid' => $appId, 'code' => $code, 'expire' => ($timestamp + 604800)]);
        return $code;
    }

    public function checkCode($appId, $appCode)
    {
        if ($row = $this->where(['appid= ? and code= ? and expire > ?'], [$appId, $appCode, time()])->fetch()) {
            $this->where(['appid= ? and code= ?'], [$appId, $appCode])->delete();
            return $row['uid'];
        } else {
            return null;
        }
    }

    public function deleteCode($appId, $appCode)
    {
        return $this->where(['appid= ? and code= ?'], [$appId, $appCode])->delete();
    }
}