<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2018/1/1 16:45
 */
class ApiModel extends Model
{
    protected array $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'name' => ['text', 'not null'],
        'client_id' => ['text', 'not null'],
        'client_secret' => ['text', 'not null'],
        'redirect_uri' => ['text', 'not null'],
        'url' => ['text', 'not null'],
        'icon' => ['text', 'not null'],
        'type' => ['integer'],
        'scope' => ['text'],
    ];
    protected array $_pk = ['id'];
    protected string $_ai = 'id';
    protected static array $FIELDS = ['id', 'name', 'type', 'url', 'icon', 'redirect_uri', 'scope'];

    public function check($clientId): ?array
    {
        if ($api = $this->where(['client_id=?'], [$clientId])->fetch(self::$FIELDS)) {
            $api['scope'] = explode('|', $api['scope']);
            return $api;
        } else {
            return null;
        }
    }

    public function validate($clientId, $clientSecret): ?array
    {
        if ($api = $this->where(['client_id=? and client_secret=?'], [$clientId, $clientSecret])->fetch(self::$FIELDS)) {
            $api['scope'] = explode('|', $api['scope']);
            return $api;
        } else {
            return null;
        }
    }

    public function getAuthorByClientId($clientId)
    {
        if ($row = $this->where(['client_id=?'], [$clientId])->fetch(['uid'])) {
            return $row['uid'];
        } else {
            return null;
        }
    }

    public function getAuthorByAppId($aid)
    {
        if ($row = $this->where(['id=?'], [$aid])->fetch(['uid'])) {
            return $row['uid'];
        } else {
            return null;
        }
    }
}