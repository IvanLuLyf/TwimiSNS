<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2019/3/5 23:23
 */
class ChannelModel extends Model
{
    protected array $_column = [
        'cid' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'name' => ['text', 'not null'],
        'avatar' => ['text'],
        'description' => ['text']
    ];
    protected array $_pk = ['cid'];
    protected string $_ai = 'cid';

    public function getChannelById($id)
    {
        return $this->where('cid=:c', ['c' => $id])->fetch();
    }

    public function getChannelByName($name)
    {
        return $this->where('name=:n', ['n' => $name])->fetch();
    }
}