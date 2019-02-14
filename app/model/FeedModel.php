<?php

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/1/1
 * Time: 20:59
 */
class FeedModel extends Model
{
    public function listFeed($uid, $page = 1)
    {
        return $this->join(DB_PREFIX . 'friend',
            [DB_PREFIX . "feed.username=" . DB_PREFIX . "friend.username AND " . DB_PREFIX . "friend.uid=$uid AND " . DB_PREFIX . "friend.state=2"],
            "LEFT")
            ->join(DB_PREFIX . 'like',
                [DB_PREFIX . "feed.tid=" . DB_PREFIX . "like.tid and " . DB_PREFIX . "like.aid = 3 and " . DB_PREFIX . "like.uid=$uid"],
                "LEFT")
            ->where([DB_PREFIX . "friend.uid = :u OR " . DB_PREFIX . "feed.uid= :u"], ['u' => $uid])
            ->order(["tid desc"])
            ->limit(20, ($page - 1) * 20)
            ->fetchAll(DB_PREFIX . "feed.*," . DB_PREFIX . "friend.notename,(" . DB_PREFIX . "like.state is not null) as islike");
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
        return $this->where(["tid = ?"], [$tid])->fetch();
    }

    public function likeFeed($tid)
    {
        $row = $this->where(["tid = :tid"], [':tid' => $tid])->fetch();
        $updates = array('like_num' => intval($row['like_num']) + 1);
        $this->where(["tid = :tid"], [':tid' => $tid])->update($updates);
        $row = $this->where(["tid = :tid"], [':tid' => $tid])->fetch();
        return intval($row['like_num']);
    }
}