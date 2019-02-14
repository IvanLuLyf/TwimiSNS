<?php

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/1/2
 * Time: 1:17
 */
class NotificationModel extends Model
{
    public function getUnreadCnt($uid)
    {
        return $this->where(["uid = ? and is_read=0"], [$uid])->fetch("count(*) as noticnt");
    }

    public function getNotice($uid)
    {
        $notices = $this->join(
            DB_PREFIX . 'friend',
            [DB_PREFIX . "notification.from_uid=" . DB_PREFIX . "friend.fuid AND " . DB_PREFIX . "friend.uid=$uid AND " . DB_PREFIX . "friend.state=2"],
            'LEFT')
            ->where([DB_PREFIX . "notification.uid=? AND " . DB_PREFIX . "notification.uid!=" . DB_PREFIX . "notification.from_uid AND " . DB_PREFIX . "notification.is_read=0"], [$uid])
            ->order(["nid desc"])
            ->fetchAll(DB_PREFIX . 'notification.*,' . DB_PREFIX . 'friend.notename');
        $this->where(["uid = :uid and is_read=0"], [':uid' => $uid])->update(['is_read' => 1]);
        return $notices;
    }

    public function notify($aid, $tid, $toid, $fromid, $action, $message)
    {
        if ($toid != $fromid) {
            return $this->add(['aid' => $aid, 'uid' => $toid, 'tid' => $tid, 'from_uid' => $fromid, 'action' => $action, 'message' => $message, 'timestamp' => time()]);
        }
    }
}