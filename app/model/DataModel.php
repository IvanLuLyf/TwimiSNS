<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/3/15
 * Time: 16:45
 */

class DataModel extends Model
{
    protected $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'app' => ['integer', 'not null'],
        'content' => ['text'],
    ];
    protected $_pk = ['id'];
    protected $_ai = 'id';

    public function get($uid, $app)
    {
        return $this->where("uid=:u and app=:a", ['u' => $uid, 'a' => $app])->fetch()['content'];
    }

    public function set($uid, $app, $content)
    {
        if ($row = $this->where("uid=:u and app=:a", ['u' => $uid, 'a' => $app])->fetch()) {
            return $this->where('id=:i', ['i' => $row['id']])->update(['content' => $content]);
        } else {
            return $this->add(['uid' => $uid, 'app' => $app, 'content' => $content]);
        }
    }
}