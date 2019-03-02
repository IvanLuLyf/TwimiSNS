<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/3/2
 * Time: 15:37
 */

class PayOrderModel extends Model
{
    protected $_column = [
        'id' => ['integer', 'not null'],
        'intro' => ['text', 'not null'],
        'price' => ['double', 'not null'],
        'app' => ['integer', 'not null'],
        'ticket' => ['text', 'not null'],
        'uid' => ['integer', 'default 0'],
        'timestamp' => ['text'],
    ];
    protected $_pk = ['id'];
    protected $_ai = 'id';

    public function ticket($app_id, $intro, $price)
    {
        $timestamp = time();
        $payTicket = sha1($intro . $app_id . $timestamp . rand(1, 100));
        $this->add(['intro' => $intro, 'app' => $app_id, 'ticket' => $payTicket, 'price' => $price, 'timestamp' => $timestamp]);
        return $payTicket;
    }

    public function check($payTicket)
    {
        if ($pay = $this->where('ticket = :t', ['t' => $payTicket])->fetch()) {
            return $pay['uid'];
        } else {
            return -1;
        }
    }

    public function get($payTicket)
    {
        return $this->where('ticket=:t', ['t' => $payTicket])->fetch();
    }

    public function confirm($payTicket, $uid)
    {
        if ($p = $this->where('ticket=:t and uid=0', ['t' => $payTicket])->fetch()) {
            $this->where('ticket=:t and uid=0', ['t' => $payTicket])->update(['uid' => $uid]);
            return $p;
        } else {
            return null;
        }
    }

    public function cancel($payTicket)
    {
        if ($p = $this->where('ticket=:t', ['t' => $payTicket])->fetch()) {
            $this->where('ticket=:t', ['t' => $payTicket])->update(['uid' => -1]);
            return $p;
        } else {
            return null;
        }
    }

    public function getOrderById($uid)
    {
        return $this->where('uid=:u', ['u' => $uid])->fetchAll();
    }
}