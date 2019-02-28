<?php

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/1/2
 * Time: 1:56
 */
class FriendModel extends Model
{
    protected $_column = [
        'id' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'fuid' => ['integer', 'not null'],
        'username' => ['text', 'not null'],
        'notename' => ['text', 'not null'],
        'state' => ['integer']
    ];
    protected $_pk = ['id'];
    protected $_ai = 'id';

    public function listFriend($uid, $state = 2)
    {
        $friends = $this->where(["uid = ? and state = ?"], [$uid, $state])->fetchAll();
        $names = [];
        foreach ($friends as $friend) {
            $names[] = iconv("UTF-8", "GB2312//IGNORE", $friend['notename']);
        }
        array_multisort($names, SORT_ASC, SORT_LOCALE_STRING, $friends);
        return $friends;
    }

    public function noteFriend($uid, $username, $notename)
    {
        if ($friend = $this->where(["uid = ? and username = ? and state = 2"], [$uid, $username])->fetch()) {
            $updates = array('notename' => $notename);
            if ($this->where(["uid = :uid and username = :username"], [':uid' => $uid, ':username' => $username])->update($updates)) {
                $response = ['ret' => 0, 'status' => 'ok'];
            } else {
                $response = ['ret' => 1006, 'status' => "database error"];
            }
        } else {
            $response = ['ret' => 4001, 'status' => "no friend"];
        }
        return $response;
    }

    public function addFriend($uid, $fuid, $username, $fusername, $nickname, $fnickname)
    {
        if ($username != $fusername) {
            if ($this->where(["uid = :uid and username = :username"], [':uid' => $uid, ':username' => $fusername])->fetch()) {
                $response = ['ret' => 1009, 'status' => "already exist"];
            } else {
                $this->add(['uid' => $uid, 'fuid' => $fuid, 'username' => $fusername, 'notename' => $fnickname, 'state' => 0]);
                $this->add(['uid' => $fuid, 'fuid' => $uid, 'username' => $username, 'notename' => $nickname, 'state' => 1]);
                $response = ['ret' => 0, 'status' => "ok"];
            }
        } else {
            $response = ['ret' => 1005, 'status' => "invalid username"];
        }
        return $response;
    }

    public function acceptFriend($uid, $fuid, $username, $fusername)
    {
        if ($row = $this->where(["uid = :uid and username = :username and state = 1"], [':uid' => $uid, ':username' => $fusername])->fetch()) {
            $updates = ['state' => 2];
            $this->where(["uid = :uid and username= :username"], [':uid' => $uid, ':username' => $fusername])->update($updates);
            $this->where(["uid = :uid and username= :username"], [':uid' => $fuid, ':username' => $username])->update($updates);
            $response = ['ret' => 0, 'status' => "ok"];
        } else {
            $response = ['ret' => 1005, 'status' => "invalid username"];
        }
        return $response;
    }
}