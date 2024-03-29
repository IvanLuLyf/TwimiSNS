<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2018/1/2 1:56
 */
class FriendModel extends Model
{
    const STATE_REQUEST = 0;
    const STATE_PENDING = 1;
    const STATE_FRIEND = 2;

    protected array $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'friend' => ['integer', 'not null'],
        'username' => ['text', 'not null'],
        'remark' => ['text', 'not null'],
        'state' => ['integer']
    ];
    protected array $_pk = ['id'];
    protected string $_ai = 'id';

    public function listFriend($uid, $state = self::STATE_FRIEND)
    {
        $friends = $this->where(['uid = ? and state = ?'], [$uid, $state])->fetchAll();
        $names = [];
        foreach ($friends as $friend) {
            $names[] = iconv('UTF-8', 'GB2312//IGNORE', $friend['remark']);
        }
        array_multisort($names, SORT_ASC, SORT_LOCALE_STRING, $friends);
        return $friends;
    }

    public function getRemark($uid, $friend_uid)
    {
        if ($friend = $this->where(['uid=? and friend=?'], [$uid, $friend_uid])->fetch(['remark'])) {
            return $friend['remark'];
        }
        return null;
    }

    public function getRemarkByUsername($uid, $friend_username)
    {
        if ($friend = $this->where(['uid=? and username=?'], [$uid, $friend_username])->fetch(['remark'])) {
            return $friend['remark'];
        }
        return null;
    }

    public function remarkFriend($uid, $username, $remark): array
    {
        if ($this->where(['uid=? and username=? and state=?'], [$uid, $username, self::STATE_FRIEND])->fetch()) {
            $updates = ['remark' => $remark];
            if ($this->where(['uid = :u and username = :un'], ['u' => $uid, 'un' => $username])->update($updates)) {
                return ['ret' => 0, 'status' => 'ok'];
            } else {
                return ['ret' => -6, 'status' => 'database error'];
            }
        } else {
            return ['ret' => 4001, 'status' => 'user is not a friend'];
        }
    }

    public function addFriend($uid, $friend, $username, $fusername, $nickname, $fnickname): array
    {
        if ($username == $fusername) {
            return ['ret' => 1004, 'status' => 'invalid username'];
        }
        if ($this->where(['uid = :u and username = :un'], ['u' => $uid, 'un' => $fusername])->fetch()) {
            return ['ret' => 4002, 'status' => 'user is already a friend'];
        }
        $this->add(['uid' => $uid, 'friend' => $friend, 'username' => $fusername, 'remark' => $fnickname, 'state' => self::STATE_REQUEST]);
        $this->add(['uid' => $friend, 'friend' => $uid, 'username' => $username, 'remark' => $nickname, 'state' => self::STATE_PENDING]);
        return ['ret' => 0, 'status' => 'ok'];
    }

    public function acceptFriend($uid, $friend, $username, $fusername): array
    {
        if ($this->where(['uid = :u and username = :un and state = 1'], ['u' => $uid, 'un' => $fusername])->fetch()) {
            $updates = ['state' => self::STATE_FRIEND];
            $this->where(["uid = :u and username= :un"], ['u' => $uid, 'un' => $fusername])->update($updates);
            $this->where(["uid = :u and username= :un"], ['u' => $friend, 'un' => $username])->update($updates);
            return ['ret' => 0, 'status' => 'ok'];
        } else {
            return ['ret' => 1004, 'status' => 'invalid username'];
        }
    }
}