<?php

use BunnyPHP\Model;

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
        'extra' => ['text'],
        'timestamp' => ['text'],
    ];
    protected $_pk = ['tid'];
    protected $_ai = 'tid';

    public function getPostByPage($page = 1, $size = 20)
    {
        return $this->join(UserModel::class, ['username'], ['nickname'])
            ->order(['tid desc'])->limit($size, ($page - 1) * $size)->fetchAll();
    }

    public function getTotal()
    {
        return $this->fetch("count(*) num")['num'];
    }

    public function search($word, $page = 1, $size = 20): array
    {
        $like_word = '%' . $word . '%';
        $posts = $this->join(UserModel::class, ['username'], ['nickname'])
            ->where('title like :wt or content like :wc', ['wt' => $like_word, 'wc' => $like_word])->order(['tid desc'])->limit($size, ($page - 1) * $size)
            ->fetchAll();
        $total = $this->where('title like :wt or content like :wc', ['wt' => $like_word, 'wc' => $like_word])->fetch("count(*) num")['num'];
        return ['posts' => $posts, 'total' => $total];
    }

    public function getPostById($id)
    {
        return $this->join(UserModel::class, ['username'], ['nickname'])
            ->where("tid=:tid", ['tid' => $id])->fetch();
    }

    public function getPostByUsername($username)
    {
        return $this->where('username=:un', ['un' => $username])->order(['tid desc'])->fetchAll();
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