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

    public function check($clientId, $accessToken)
    {
        if ($row = $this->where(["client_id = ? and token = ? and expire > ?"], [$clientId, $accessToken, time()])->fetch()) {
            return $row['uid'];
        } else {
            return 0;
        }
    }

    public function get($uid, $clientId, $appType)
    {
        $timestamp = time();
        if (intval($appType) == 1 || intval($appType) == 2) {
            $seconds = 1296000;
        } else {
            $seconds = 172800;
        }
        if ($tokenRow = $this->where(["client_id = :ak and uid = :u"], ['ak' => $clientId, 'u' => $uid])->fetch()) {
            if ($timestamp < intval($tokenRow['expire'])) {
                $token = $tokenRow['token'];
                $expire = $tokenRow['expire'];
            } else {
                $token_id = $tokenRow['id'];
                $token = md5($uid + $clientId + $timestamp);
                $expire = $timestamp + $seconds;
                $this->where(["id = :id"], ['id' => $token_id])->update(['token' => $token, 'expire' => $expire]);
            }
            $rowId = $tokenRow['id'];
        } else {
            $token = md5($uid + $clientId + $timestamp);
            $expire = $timestamp + $seconds;
            $rowId = $this->add(['uid' => $uid, 'client_id' => $clientId, 'token' => $token, 'expire' => $expire]);
        }
        $response = ['token' => $token, 'expire' => $expire];
        if (intval($appType) == 1) {
            $response['refresh_token'] = md5(md5($rowId) . $token . md5($clientId));
        }
        return $response;
    }

    public function refresh($accessToken, $clientId, $refreshToken)
    {
        $tokenRow = $this->where(["client_id = ? and token = ?"], [$clientId, $accessToken])->fetch();
        $token_id = $tokenRow['id'];
        if (md5(md5($token_id) . $accessToken . md5($clientId)) == $refreshToken) {
            $timestamp = time();
            $token = md5($tokenRow['uid'] + $clientId + $timestamp);
            $expire = $timestamp + 1296000;
            $this->where(["id = :id"], ['id' => $token_id])->update(['token' => $token, 'expire' => $expire]);
            return ['token' => $token, 'expire' => $expire, 'refresh_token' => md5(md5($token_id) . $token . md5($clientId))];
        } else {
            return null;
        }
    }
}