<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/2/13
 * Time: 15:51
 */

class PostModel extends Model
{
    public function getPostByPage($page = 1, $size = 5)
    {
        return $this->order(['tid desc'])->limit($size, ($page - 1) * $size)->fetchAll();
    }

    public function getPostById($id)
    {
        return $this->where("tid=:tid", ['tid' => $id])->fetch();
    }

    public function getPostByUsername($username)
    {
        return $this->where("username=:un", ['un' => $username])->order(['tid desc'])->fetchAll();
    }

    public function sendPost($user, $title, $content)
    {
        if ($user != null && $title != null && $content != null) {
            $post = ['username' => $user['username'], 'nickname' => $user['nickname'], 'title' => $title, 'content' => $content, 'timestamp' => time()];
            return $this->add($post);
        } else {
            return -1;
        }
    }
}