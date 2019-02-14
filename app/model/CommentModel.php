<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/10/25
 * Time: 0:52
 */

class CommentModel extends Model
{
    public function listComment($tid, $aid, $page = 1)
    {
        return $this->where('tid = :t and aid = :a', ['t' => $tid, 'a' => $aid])->limit(20, ($page - 1) * 20)->fetchAll();
    }

    public function sendComment($tid, $aid, $user, $content)
    {
        if ($user != null && $tid != null && $aid != null && $content != null) {
            $comment = ['tid' => $tid, 'aid' => $aid, 'username' => $user['username'], 'nickname' => $user['nickname'], 'content' => $content, 'timestamp' => time()];
            return $this->add($comment);
        } else {
            return -1;
        }
    }
}