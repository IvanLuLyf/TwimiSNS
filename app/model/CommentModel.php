<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2018/10/25 0:52
 */
class CommentModel extends Model
{
    protected array $_column = [
        'cid' => ['integer', 'not null'],
        'tid' => ['integer', 'not null'],
        'aid' => ['integer', 'not null'],
        'username' => ['varchar(16)', 'not null'],
        'nickname' => ['varchar(32)'],
        'content' => ['text', 'not null'],
        'timestamp' => ['bigint'],
    ];
    protected array $_pk = ['cid'];
    protected string $_ai = 'cid';

    public function listComment($tid, $aid, $page = 1, $uid = 0)
    {
        if ($uid == 0) {
            return $this->where('tid = :t and aid = :a', ['t' => $tid, 'a' => $aid])->limit(20, ($page - 1) * 20)->fetchAll();
        } else {
            return $this
                ->join(FriendModel::class, ['username', 'uid' => $uid, 'state' => 2], ['remark'])
                ->where('tid = :t and aid = :a', ['t' => $tid, 'a' => $aid])->limit(20, ($page - 1) * 20)
                ->fetchAll();
        }
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