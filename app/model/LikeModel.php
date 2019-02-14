<?php

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/1/1
 * Time: 22:20
 */
class LikeModel extends Model
{
    public function isLike($uid, $aid, $tid)
    {
        return $this->where(["uid = ? and tid = ? and aid = ?"], [$uid, $tid, $aid])->fetch() ? 1 : 0;
    }

    public function like($uid, $aid, $tid)
    {
        return $this->add(['uid' => $uid, 'tid' => $tid, 'aid' => $aid, 'state' => 1]);
    }

    public function unlike($uid, $aid, $tid)
    {
        return $this->where(["uid = ? and tid = ? and aid = ?"], [$uid, $tid, $aid])->delete();
    }
}