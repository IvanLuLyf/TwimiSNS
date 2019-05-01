<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/3/5
 * Time: 23:23
 */

class ChannelModel extends Model
{
    protected $_column = [
        'cid' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'name' => ['text', 'not null'],
        'avatar' => ['text'],
        'description' => ['text']
    ];
    protected $_pk = ['cid'];
    protected $_ai = 'cid';

    public function getChannelById($id)
    {
        return $this->where("cid=:c", ['c' => $id])->fetch();
    }

    public function getChannelByName($name)
    {
        return $this->where("name=:n", ['n' => $name])->fetch();
    }
}