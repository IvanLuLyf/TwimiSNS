<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Controller;

/**
 * @author IvanLu
 * @time 2019/3/2 18:25
 */
class PayController extends Controller
{
    /**
     * @filter auth pay
     */
    public function ac_pay()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $payTicket = $_POST['ticket'];
        $signature = $_POST['signature'];
        $random_str = $_POST['str'];
        $payPassword = (new PayPassModel())->getPassword($tp_user['uid']);
        if (md5(md5($payTicket . $random_str) . md5($payPassword . $random_str)) == $signature) {
            $payOrderModel = new PayOrderModel();
            if ($payInfo = $payOrderModel->confirm($payTicket, $tp_user['uid'])) {
                $apiModel = new ApiModel();
                $creditModel = new CreditModel();
                if ($creditModel->transfer($tp_user['uid'], $apiModel->getAuthorByAppId($payInfo['app']), $payInfo['price'])) {
                    $this->assignAll(['ret' => 0, 'status' => 'ok']);
                } else {
                    $payOrderModel->cancel($payTicket);
                    $this->assignAll(['ret' => 5003, 'status' => 'insufficient balance']);
                }
            } else {
                $this->assignAll(['ret' => 5002, 'status' => 'already paid']);
            }
        } else {
            $this->assignAll(['ret' => 5001, 'status' => 'wrong payment password']);
        }
        $this->render('pay/buy.php');
    }

    /**
     * @filter api requestPay
     */
    public function ac_request()
    {
        $tp_api = BunnyPHP::app()->get('tp_api');
        $intro = $_POST['intro'];
        $price = $_POST['price'];
        if ($price >= 0) {
            $payTicket = (new PayOrderModel())->ticket($tp_api['id'], $intro, $price);
            $this->assignAll(['ret' => 0, 'status' => 'ok', 'ticket' => $payTicket]);
        } else {
            $this->assignAll(['ret' => 5006, 'status' => 'invalid amount']);
        }
        $this->render('pay/request.php');
    }

    /**
     * @filter auth pay
     */
    public function ac_red_packet()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $tp_api = BunnyPHP::app()->get('tp_api');
        $num = $_POST['num'];
        $total = $_POST['total'];
        $message = $_POST['message'];
        $signature = $_POST['signature'];
        $random_str = $_POST['str'];
        $payPassword = (new PayPassModel())->getPassword($tp_user['uid']);
        if ($total > 0) {
            if (md5(md5($num . ':' . $total . $random_str) . md5($payPassword . $random_str)) == $signature) {
                $creditModel = new CreditModel();
                if ($creditModel->cut($tp_user['uid'], $total)) {
                    $rp = (new RedPacketModel())->send($tp_api['id'], $tp_user['uid'], $total, $num, $message);
                    $this->assignAll(['ret' => 0, 'status' => 'ok', 'red_packet' => $rp]);
                } else {
                    $this->assignAll(['ret' => 5003, 'status' => 'insufficient balance']);
                }
            } else {
                $this->assignAll(['ret' => 5001, 'status' => 'wrong payment password']);
            }
        } else {
            $this->assignAll(['ret' => 5006, 'status' => 'invalid amount']);
        }
        $this->render('pay/red_packet.php');
    }

    /**
     * @filter auth pay
     */
    public function ac_pick()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $tp_api = BunnyPHP::app()->get('tp_api');
        $packetId = $_POST['packet'];
        $money = (new RedPacketModel())->pick($packetId, $tp_api['id']);
        if ($money > 0) {
            $creditModel = new CreditModel();
            if ($creditModel->cut($tp_user['uid'], -$money)) {
                $this->assignAll(['ret' => 0, 'status' => 'ok', "money" => $money]);
            } else {
                $this->assignAll(['ret' => 5003, 'status' => 'insufficient balance']);
            }
        } else {
            $this->assignAll(['ret' => 5005, 'status' => 'empty red packet']);
        }
        $this->render('pay/pick.php');
    }

    /**
     * @filter api requestPay
     */
    public function ac_view()
    {
        $payTicket = $_POST['ticket'];
        $payInfo = (new PayOrderModel())->get($payTicket);
        $payInfo['paid'] = ($payInfo['uid'] > 0);
        unset($payInfo['uid']);
        $this->assignAll(['ret' => 0, 'status' => 'ok'])->assignAll($payInfo)->render('pay/view.php');
    }

    /**
     * @filter auth pay
     */
    public function ac_balance()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $credit = new CreditModel();
        $this->assignAll(['ret' => 0, 'status' => 'ok', 'credit' => intval($credit->balance($tp_user['uid']))])->render('pay/balance.php');
    }

    /**
     * @filter auth pay
     * @filter csrf
     */
    public function ac_start_get()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        if (intval((new CreditModel())->balance($tp_user['uid'])) == -1) {
            $this->render('pay/start.php');
        } else {
            $this->redirect('pay', 'balance');
        }
    }

    /**
     * @filter auth pay
     * @filter csrf check
     * @param EmailService $service
     * @param string $pass not_empty()
     */
    public function ac_start_post(EmailService $service, string $pass)
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $credit = new CreditModel();
        (new PayPassModel())->setPassword($tp_user['uid'], md5($pass));
        $service->sendMail('email/pay_start.html', ['nickname' => $tp_user['nickname'], 'site' => TP_SITE_NAME], $tp_user['email'], '您已开通' . TP_SITE_NAME . "支付服务");
        $this->assignAll(['ret' => 0, 'status' => 'ok', 'credit' => $credit->start($tp_user['uid'])])->render('pay/start.php');
    }
}