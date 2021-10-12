<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2019/2/13 Time: 15:51
 */
class ArticleModel extends Model
{
    protected array $_column = [
        'tid' => ['integer', 'not null'],
        'cid' => ['integer', 'not null'],
        'title' => ['text', 'not null'],
        'summary' => ['text'],
        'content' => ['text', 'not null'],
        'timestamp' => ['text'],
    ];
    protected array $_pk = ['tid'];
    protected string $_ai = 'tid';

    public function getArticlesByPage($page = 1, $size = 5)
    {
        return $this->order(['timestamp desc'])->limit($size, ($page - 1) * $size)->fetchAll();
    }

    public function getArticlesByChannel($channelId, $page = 1, $size = 5)
    {
        return $this->where("cid=:c", ['c' => $channelId])->order(['timestamp desc'])->limit($size, ($page - 1) * $size)->fetchAll();
    }

    public function getArticleById($id)
    {
        return $this->where("tid=:t", ['t' => $id])->fetch();
    }
}