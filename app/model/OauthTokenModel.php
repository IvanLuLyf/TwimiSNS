<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2018/1/1 17:07
 */
class OauthTokenModel extends Model
{
    protected array $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'client_id' => ['text', 'not null'],
        'access_token' => ['text', 'not null'],
        'scope' => ['text'],
        'expire' => ['text']
    ];
    protected array $_pk = ['id'];
    protected string $_ai = 'id';

    public function check($clientId, $accessToken)
    {
        if ($tokenInfo = $this->where(['client_id=? and access_token=? and expire>?'], [$clientId, $accessToken, time()])->fetch(['uid', 'scope'])) {
            $tokenInfo['scope'] = explode('|', $tokenInfo['scope']);
            return $tokenInfo;
        } else {
            return 0;
        }
    }

    public function generate($uid, $clientId, $appType, $scope = []): array
    {
        $timestamp = time();
        sort($scope, SORT_STRING);
        $scopeStr = implode('|', $scope);
        if (intval($appType) == ConstUtil::APP_SYSTEM || intval($appType) == ConstUtil::APP_NORMAL) {
            $seconds = 1296000;
        } else {
            $seconds = 172800;
        }
        if ($tokenRow = $this->where(['client_id=:ak and uid=:u'], ['ak' => $clientId, 'u' => $uid])->fetch()) {
            $tokenId = $tokenRow['id'];
            $updates = [];
            if ($timestamp < intval($tokenRow['expire'])) {
                $accessToken = $tokenRow['access_token'];
                $expire = $tokenRow['expire'];
            } else {
                $accessToken = $this->createToken($uid, $clientId, $timestamp);
                $expire = $timestamp + $seconds;
                $updates = ['access_token' => $accessToken, 'expire' => $expire];
            }
            if ($tokenRow['scope'] != $scopeStr) {
                $updates['scope'] = $scopeStr;
            }
            if ($updates) $this->where(['id = :id'], ['id' => $tokenId])->update($updates);
        } else {
            $accessToken = $this->createToken($uid, $clientId, $timestamp);
            $expire = $timestamp + $seconds;
            $tokenId = $this->add(['uid' => $uid, 'client_id' => $clientId, 'access_token' => $accessToken, 'expire' => $expire, 'scope' => $scopeStr]);
        }
        $response = ['token' => $accessToken, 'access_token' => $accessToken, 'expire' => $expire];
        if ($scopeStr) {
            $response['scope'] = $scope;
        }
        if (intval($appType) == ConstUtil::APP_SYSTEM) {
            $response['refresh_token'] = $this->createRefreshToken($tokenId, $clientId, $accessToken);
        }
        return $response;
    }

    public function refresh($accessToken, $clientId, $refreshToken): ?array
    {
        $tokenRow = $this->where(['client_id=? and access_token=?'], [$clientId, $accessToken])->fetch();
        $tokenId = $tokenRow['id'];
        if ($this->createRefreshToken($tokenId, $clientId, $accessToken) === $refreshToken) {
            $timestamp = time();
            $newToken = $this->createToken($tokenRow['uid'], $clientId, $timestamp);
            $expire = $timestamp + 1296000;
            $this->where(["id=:id"], ['id' => $tokenId])->update(['access_token' => $newToken, 'expire' => $expire]);
            return ['token' => $newToken, 'access_token' => $newToken, 'expire' => $expire, 'refresh_token' => $this->createRefreshToken($tokenId, $clientId, $newToken)];
        } else {
            return null;
        }
    }

    private function createToken($uid, $clientId, $timestamp): string
    {
        return md5($uid . $clientId . $timestamp);
    }

    private function createRefreshToken($tokenId, $clientId, $accessToken): string
    {
        return md5(md5($tokenId) . $accessToken . md5($clientId));
    }
}