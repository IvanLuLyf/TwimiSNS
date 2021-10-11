<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2018/1/8 13:29
 */
class OauthCodeModel extends Model
{
    protected array $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'app_id' => ['integer', 'not null'],
        'code' => ['text', 'not null'],
        'expire' => ['text']
    ];
    protected array $_pk = ['id'];
    protected string $_ai = 'id';

    public function getCode($clientId, $appId, $uid, $timestamp): string
    {
        $code = md5($uid . $clientId . $timestamp);
        $this->add(['uid' => $uid, 'app_id' => $appId, 'code' => $code, 'expire' => ($timestamp + 604800)]);
        return $code;
    }

    public function checkCode($appId, $appCode)
    {
        if ($row = $this->where(['app_id=? and code=? and expire>?'], [$appId, $appCode, time()])->fetch()) {
            $this->where(['app_id=? and code=?'], [$appId, $appCode])->delete();
            return $row['uid'];
        } else {
            return null;
        }
    }

    public function deleteCode($appId, $appCode)
    {
        return $this->where(['app_id=? and code=?'], [$appId, $appCode])->delete();
    }
}