<?php

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/1/1
 * Time: 17:07
 */
class OauthTokenModel extends Model
{
    protected $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'client_id' => ['text', 'not null'],
        'token' => ['text', 'not null'],
        'expire' => ['text']
    ];
    protected $_pk = ['id'];
    protected $_ai = 'id';

    public function check($appKey, $appToken)
    {
        if ($row = $this->where(["client_id = ? and token = ? and expire > ?"], [$appKey, $appToken, time()])->fetch()) {
            return $row['uid'];
        } else {
            return 0;
        }
    }

    public function get($uid, $appKey)
    {
        $timestamp = time();
        if ($tokenRow = $this->where(["client_id = :ak and uid = :u"], ['ak' => $appKey, 'u' => $uid])->fetch()) {
            if ($timestamp < intval($tokenRow['expire'])) {
                $token = $tokenRow['token'];
                $expire = $tokenRow['expire'];
            } else {
                $token_id = $tokenRow['id'];
                $token = md5($uid + $appKey + $timestamp);
                $expire = $timestamp + 604800;
                $this->where(["id = :id"], ['id' => $token_id])->update(['token' => $token, 'expire' => $expire]);
            }
        } else {
            $token = md5($uid + $appKey + $timestamp);
            $expire = $timestamp + 604800;
            $this->add(['uid' => $uid, 'client_id' => $appKey, 'token' => $token, 'expire' => $expire]);
        }
        $response = ['token' => $token, 'expire' => $expire];
        return $response;
    }
}