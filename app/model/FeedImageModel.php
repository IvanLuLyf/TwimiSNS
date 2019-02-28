<?php

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/1/2
 * Time: 17:46
 */
class FeedImageModel extends Model
{
    protected $_column = [
        'id' => ['integer', 'not null'],
        'tid' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'url' => ['text', 'not null'],
    ];
    protected $_pk = ['id'];
    protected $_ai = 'id';

    public function upload($uid, $tid, $url)
    {
        return $this->add(['uid' => $uid, 'tid' => $tid, 'url' => $url]);
    }

    public function getFeedImageByTid($tid)
    {
        $row = $this->where(["tid = ?"], [$tid])->fetchAll('url');
        return $row ? $row : null;
    }
}