<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Controller;

/**
 * @author IvanLu
 * @time 2019/3/2 18:25
 */
class PayController extends Controller
{
    /** @return array<string, mixed>|null */
    private function payWalletSnapshot(UserService $userService): ?array
    {
        $tp_user = $userService->getLoginUser();
        if ($tp_user === null) {
            return null;
        }
        $uid = (int) $tp_user['uid'];
        $raw = (new CreditModel())->balance($uid);
        $stored = (new PayPassModel())->getPassword($uid);
        $hasPayPass = $stored !== null && $stored !== '';
        $active = $raw != -1;

        return [
            'wallet_active' => $active,
            'credit' => $active ? (float) $raw : null,
            'has_pay_pass' => $hasPayPass,
            'need_setup' => $raw == -1,
            'need_pay_pass' => $active && !$hasPayPass,
        ];
    }

    /** @return array<string, mixed> */
    private function walletStartApply(UserService $userService, EmailService $service, string $pass): array
    {
        $tp_user = $userService->getLoginUser();
        if ($tp_user === null) {
            return ['ret' => 2002, 'status' => 'login required', 'tp_error_msg' => '请先登录'];
        }
        $credit = new CreditModel();
        $bal = $credit->balance($tp_user['uid']);
        $stored = (new PayPassModel())->getPassword($tp_user['uid']);
        $hasPayPass = $stored !== null && $stored !== '';
        if ($bal != -1 && $hasPayPass) {
            return [
                'ret' => 0,
                'status' => 'ok',
                'already_active' => true,
                'credit' => (float) $bal,
            ];
        }

        $pass = trim($pass);
        $len = function_exists('mb_strlen') ? mb_strlen($pass) : strlen($pass);
        if ($len < 1 || $len > 6) {
            return ['ret' => -7, 'status' => 'invalid pass', 'tp_error_msg' => '支付密码为 1～6 位'];
        }
        (new PayPassModel())->setPassword($tp_user['uid'], md5($pass));

        if ($bal == -1) {
            $service->sendMail(
                'email/pay_start.html',
                ['nickname' => $tp_user['nickname'], 'site' => TP_SITE_NAME],
                $tp_user['email'],
                '您已开通' . TP_SITE_NAME . '支付服务',
            );
            $credit->start($tp_user['uid']);
        }

        return [
            'ret' => 0,
            'status' => 'ok',
            'credit' => (float) $credit->balance($tp_user['uid']),
        ];
    }

    /**
     * @filter auth pay
     */
    public function ac_wallet_get(UserService $userService): void
    {
        $snap = $this->payWalletSnapshot($userService);
        if ($snap === null) {
            $this->assignAll(['ret' => 2002, 'status' => 'login required', 'tp_error_msg' => '请先登录'])->render('app.php');
            return;
        }
        $this->assignAll([
            'ret' => 0,
            'status' => 'ok',
            'wallet_active' => $snap['wallet_active'],
            'credit' => $snap['credit'],
            'has_pay_pass' => $snap['has_pay_pass'],
        ])->render('app.php');
    }

    public function ac_json_start_state(UserService $userService): void
    {
        $snap = $this->payWalletSnapshot($userService);
        if ($snap === null) {
            $this->assignAll(['ret' => 2002, 'status' => 'login required', 'tp_error_msg' => '请先登录'])->render('app.php');
            return;
        }
        $this->assignAll([
            'ret' => 0,
            'status' => 'ok',
            'need_setup' => $snap['need_setup'],
            'need_pay_pass' => $snap['need_pay_pass'],
            'credit' => $snap['credit'],
        ])->render('app.php');
    }

    /**
     * @filter csrf check
     * @param EmailService $service
     * @param string $pass not_empty()
     */
    public function ac_json_start_post(UserService $userService, EmailService $service, string $pass): void
    {
        $this->assignAll($this->walletStartApply($userService, $service, $pass))->render('app.php');
    }

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
        $this->render('app.php');
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
        $this->render('app.php');
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
        $this->render('app.php');
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
        $this->render('app.php');
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
        $this->assignAll(['ret' => 0, 'status' => 'ok'])->assignAll($payInfo)->render('app.php');
    }

    /**
     * @filter auth pay
     */
    public function ac_balance()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $credit = new CreditModel();
        $this->assignAll(['ret' => 0, 'status' => 'ok', 'credit' => intval($credit->balance($tp_user['uid']))])->render('app.php');
    }

    /**
     * @filter auth pay
     */
    public function ac_start_get(): void
    {
        $this->render('app.php');
    }

    /**
     * @filter auth pay
     * @filter csrf check
     * @param EmailService $service
     * @param string $pass not_empty()
     */
    public function ac_start_post(UserService $userService, EmailService $service, string $pass): void
    {
        $this->assignAll($this->walletStartApply($userService, $service, $pass))->render('app.php');
    }
}
