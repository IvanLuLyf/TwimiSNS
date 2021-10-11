<?php

use BunnyPHP\Model;

/**
 * @author IvanLu
 * @time 2019/3/28 14:38
 */
class RedPacketModel extends Model
{
    protected array $_column = [
        'id' => ['integer', 'not null'],
        'app' => ['integer', 'not null'],
        'uid' => ['integer', 'not null'],
        'total' => ['double', 'not null'],
        'balance' => ['double', 'not null'],
        'num' => ['integer', 'not null'],
        'remain' => ['integer', 'not null'],
        'message' => ['text'],
    ];
    protected array $_pk = ['id'];
    protected string $_ai = 'id';

    public function send($app, $uid, $total, $num, $message)
    {
        return $this->add(['app' => $app, 'uid' => $uid, 'total' => $total, 'balance' => $total, 'num' => $num, 'remain' => $num, 'message' => $message]);
    }

    public function pick($id, $app)
    {
        $packet = $this->where(["id=:id and app=:a"], ['id' => $id, 'a' => $app])->fetch();
        if ($packet['remain'] > 0) {
            if ($packet['remain'] == 1) {
                $money = $packet['balance'];
            } else {
                $balance = $packet['balance'];
                $d_size = $packet['num'] - $packet['remain'];
                $safe_total = ($balance - ($d_size) * 0.01) / ($d_size);
                $money = mt_rand(1, $safe_total * 100) / 100;
            }
            $flag = $this->where(['id=:id'], ['id' => $id])->update(['m' => $money], 'balance=balance-:m,remain=remain-1');
            if ($flag > 0) {
                return $money;
            } else {
                return 0;
            }
        }
        return 0;
    }
}