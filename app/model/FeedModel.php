<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2018/1/1 20:59
 */
class FeedModel extends Model
{
    protected array $_column = [
        'tid' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'username' => ['varchar(16)', 'not null'],
        'nickname' => ['varchar(32)'],
        'source' => ['text'],
        'content' => ['text', 'not null'],
        'timestamp' => ['bigint'],
        'share_num' => ['integer', 'default 0'],
        'comment_num' => ['integer', 'default 0'],
        'like_num' => ['integer', 'default 0'],
        'image' => ['integer'],
    ];
    protected array $_pk = ['tid'];
    protected string $_ai = 'tid';

    public function listFeed($uid, $page = 1)
    {
        $tb = $this->_table;
        $ft = FriendModel::name();
        return $this->join(FriendModel::class, ['username', 'uid' => $uid, 'state' => 2], ['remark'])
            ->join(LikeModel::class, ['tid', 'aid' => ConstUtil::MOD_FEED, 'uid' => $uid], [['state', '(%s is not null) as islike']])
            ->where(["{$ft}.uid = :u or {$tb}.uid= :um"], ['u' => $uid, 'um' => $uid])
            ->order(["tid desc"])
            ->limit(20, ($page - 1) * 20)
            ->fetchAll();
    }

    public function userFeed($username, $page = 1)
    {
        return $this->where('username=:u', ['u' => $username])->order(['tid desc'])->limit(20, ($page - 1) * 20)->fetchAll();
    }

    public function sendFeed($user, $content, $source, $image = 0)
    {
        if ($user != null && $source != null && $content != null) {
            return $this->add([
                'uid' => $user['uid'],
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'source' => $source,
                'content' => $content,
                'timestamp' => time(),
                'image' => $image
            ]);
        } else {
            return -1;
        }
    }

    public function getFeed($tid)
    {
        return $this->where(['tid = ?'], [$tid])->fetch();
    }

    public function likeFeed($tid): int
    {
        $this->where(['tid = :t'], ['t' => $tid])->update([], 'like_num=like_num+1');
        $row = $this->where(['tid = :t'], ['t' => $tid])->fetch();
        return intval($row['like_num']);
    }
}