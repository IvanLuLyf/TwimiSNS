<?php

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/1/1
 * Time: 16:45
 */
class ApiModel extends Model
{
    protected $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'name' => ['text', 'not null'],
        'client_id' => ['text', 'not null'],
        'client_secret' => ['text', 'not null'],
        'redirect_uri' => ['text', 'not null'],
        'url' => ['text', 'not null'],
        'type' => ['integer'],
        'auth' => ['integer'],
    ];
    protected $_pk = ['id'];
    protected $_ai = 'id';

    public function check($clientId)
    {
        if ($row = $this->where(["client_id = ?"], [$clientId])->fetch()) {
            return [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => $row['type'],
                'url' => $row['url'],
                'redirect_uri' => $row['redirect_uri'],
                'canGetInfo' => (intval($row['auth']) & 1) && true,
                'canFeed' => (intval($row['auth']) & 2) && true,
                'canGetFriend' => (intval($row['auth']) & 4) && true,
                'canRequestPay' => (intval($row['auth']) & 8) && true,
                'canPay' => (intval($row['auth']) & 16) && true
            ];
        } else {
            return null;
        }
    }

    public function validate($clientId, $clientSecret)
    {
        if ($row = $this->where(["client_id = ? and client_secret = ?"], [$clientId, $clientSecret])->fetch()) {
            return [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => $row['type'],
                'url' => $row['url'],
                'redirect_uri' => $row['redirect_uri'],
                'canGetInfo' => (intval($row['auth']) & 1) && true,
                'canFeed' => (intval($row['auth']) & 2) && true,
                'canGetFriend' => (intval($row['auth']) & 4) && true,
                'canRequestPay' => (intval($row['auth']) & 8) && true,
                'canPay' => (intval($row['auth']) & 16) && true
            ];
        } else {
            return null;
        }
    }

    public function getAuthorByClientId($clientId)
    {
        if ($row = $this->where(["client_id = ?"], [$clientId])->fetch()) {
            return $row['uid'];
        } else {
            return null;
        }
    }

    public function getAuthorByAppId($aid)
    {
        if ($row = $this->where(["id = ?"], [$aid])->fetch()) {
            return $row['uid'];
        } else {
            return null;
        }
    }
}