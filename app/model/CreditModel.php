<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2026/05/03 15:30
 */
class CreditModel extends Model
{
    protected $_column = [
        'uid' => ['integer', 'not null'],
        'credit' => ['double', 'not null', 'default 0'],
    ];
    protected $_pk = ['uid'];

    public function transfer($from_uid, $to_uid, $credit): bool
    {
        $credit = doubleval($credit);
        if ($from_uid === $to_uid) {
            return true;
        }
        if ($this->balance($to_uid) == -1) {
            $this->start($to_uid);
        }
        $from_credit = $this->where(["uid = :u"], ['u' => $from_uid])->fetch();
        $to_credit = $this->where(["uid = :u"], ['u' => $to_uid])->fetch();
        if ($from_credit != null && $to_credit != null) {
            $balance = doubleval($from_credit['credit']);
            if ($balance >= $credit) {
                $flag = ($this->where(['uid=:u'], ['u' => $from_uid])->update(['c' => $credit], 'credit=credit-:c') > 0);
                $flag &= ($this->where(['uid=:u'], ['u' => $to_uid])->update(['c' => $credit], 'credit=credit+:c') > 0);
                return $flag;
            }
        }
        return false;
    }

    public function cut($uid, $credit): bool
    {
        $from_credit = $this->where(["uid = :u"], ['u' => $uid])->fetch();
        if ($from_credit != null) {
            $balance = doubleval($from_credit['credit']);
            if ($balance >= $credit) {
                $flag = $this->where(['uid=:u'], ['u' => $uid])->update(['c' => $credit], 'credit=credit-:c');
                return $flag > 0;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function start($uid): int
    {
        if ($this->where(['uid=:u'], ['u' => $uid])->fetch()) {
            return -1;
        } else {
            $this->add(['uid' => $uid, 'credit' => 0]);
            return 0;
        }
    }

    public function balance($uid)
    {
        if ($credit = $this->where(['uid=:u'], ['u' => $uid])->fetch()) {
            return $credit['credit'];
        } else {
            return -1;
        }
    }
}