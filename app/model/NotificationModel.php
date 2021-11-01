<?php

use BunnyPHP\Model;

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/1/2
 * Time: 1:17
 */
class NotificationModel extends Model
{
    protected $_column = [
        'nid' => ['integer', 'not null'],
        'aid' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'tid' => ['integer', 'not null'],
        'from_uid' => ['integer', 'not null'],
        'is_read' => ['integer', 'not null', 'default 0'],
        'action' => ['text'],
        'message' => ['text'],
        'timestamp' => ['text'],
    ];
    protected $_pk = ['nid'];
    protected $_ai = 'nid';

    public function getUnreadCnt($uid)
    {
        $note_count = $this->where(["uid = ? and is_read=0"], [$uid])->fetch("count(*) as note_count")['note_count'];
        $note_uid = $this->where(["uid = ? and is_read=0"], [$uid])->order("timestamp desc")->fetch("from_uid")['from_uid'] ?? 0;
        return [$note_count, $note_uid];
    }

    public function getNotice($uid)
    {
        $tb = $this->_table;
        $notices = $this->join(FriendModel::class, [['friend', 'from_uid'], 'uid' => $uid, 'state' => 2], ['remark'])
            ->where(["{$tb}.uid=:u AND {$tb}.uid!={$tb}.from_uid AND {$tb}.is_read=0"], ['u' => $uid])
            ->order(["nid desc"])
            ->fetchAll();
        $this->where(["uid = :u and is_read=0"], ['u' => $uid])->update(['is_read' => 1]);
        return $notices;
    }

    public function notify($aid, $tid, $to_uid, $from_uid, $action, $message)
    {
        if ($to_uid != $from_uid) {
            return $this->add(['aid' => $aid, 'uid' => $to_uid, 'tid' => $tid, 'from_uid' => $from_uid, 'action' => $action, 'message' => $message, 'timestamp' => time()]);
        } else {
            return null;
        }
    }
}