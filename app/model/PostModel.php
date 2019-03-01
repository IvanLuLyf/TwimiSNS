<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/2/13
 * Time: 15:51
 */

class PostModel extends Model
{
    protected $_column = [
        'tid' => ['integer', 'not null'],
        'username' => ['varchar(16)', 'not null'],
        'title' => ['text', 'not null'],
        'content' => ['text', 'not null'],
        'timestamp' => ['text'],
    ];
    protected $_pk = ['tid'];
    protected $_ai = 'tid';

    public function getPostByPage($page = 1, $size = 20)
    {
        return $this->join(DB_PREFIX . "user", [DB_PREFIX . "post.username=" . DB_PREFIX . "user.username"], "LEFT")
            ->order(['tid desc'])->limit($size, ($page - 1) * $size)->fetchAll(DB_PREFIX . "post.*," . DB_PREFIX . "user.nickname");
    }

    public function getTotal()
    {
        return $this->fetch("count(*) num")['num'];
    }

    public function search($word, $page = 1, $size = 20)
    {
        $posts = $this->join(DB_PREFIX . "user", [DB_PREFIX . "post.username=" . DB_PREFIX . "user.username"], "LEFT")
            ->where('title like :w or content like :w', ['w' => "%$word%"])->order(['tid desc'])->limit($size, ($page - 1) * $size)
            ->fetchAll(DB_PREFIX . "post.*," . DB_PREFIX . "user.nickname");
        $total = $this->where('title like :w or content like :w', ['w' => "%$word%"])->fetch("count(*) num")['num'];
        return ['posts' => $posts, 'total' => $total];
    }

    public function getPostById($id)
    {
        return $this->join(DB_PREFIX . "user", [DB_PREFIX . "post.username=" . DB_PREFIX . "user.username"], "LEFT")
            ->where("tid=:tid", ['tid' => $id])->fetch(DB_PREFIX . "post.*," . DB_PREFIX . "user.nickname");
    }

    public function getPostByUsername($username)
    {
        return $this->where("username=:un", ['un' => $username])->order(['tid desc'])->fetchAll();
    }

    public function sendPost($user, $title, $content)
    {
        if ($user != null && $title != null && $content != null) {
            $post = ['username' => $user['username'], 'title' => $title, 'content' => $content, 'timestamp' => time()];
            return $this->add($post);
        } else {
            return -1;
        }
    }
}