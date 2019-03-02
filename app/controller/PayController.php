<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/3/2
 * Time: 18:25
 */

class PayController extends Controller
{
    /**
     * @filter auth canPay
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
                    $this->assign('ret', 0);
                    $this->assign('status', 'ok');
                } else {
                    $payOrderModel->cancel($payTicket);
                    $this->assign('ret', 5003);
                    $this->assign('status', 'no enough coin');
                }
            } else {
                $this->assign('ret', 5002);
                $this->assign('status', 'already pay');
            }
        } else {
            $this->assign('ret', 5001);
            $this->assign('status', 'invalid password');
        }
        $this->render('pay/pay.html');
    }

    /**
     * @filter api canRequestPay
     */
    public function ac_request()
    {
        $tp_api = BunnyPHP::app()->get('tp_api');
        $intro = $_POST['intro'];
        $price = $_POST['price'];
        $payTicket = (new PayOrderModel())->ticket($tp_api['id'], $intro, $price);
        $this->assign('ret', 0);
        $this->assign('status', 'ok');
        $this->assign('ticket', $payTicket);
        $this->render('pay/request.html');
    }

    /**
     * @filter api canRequestPay
     */
    public function ac_view()
    {
        $payTicket = $_POST['ticket'];
        $payInfo = (new PayOrderModel())->get($payTicket);
        $payInfo['paid'] = ($payInfo['uid'] > 0);
        unset($payInfo['uid']);
        $this->assign('ret', 0);
        $this->assign('status', 'ok');
        $this->assignAll($payInfo);
        $this->render('pay/view.html');
    }

    /**
     * @filter auth canPay
     */
    public function ac_balance()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $credit = new CreditModel();
        $this->assign('ret', 0);
        $this->assign('status', 'ok');
        $this->assign('credit', intval($credit->balance($tp_user['uid'])));
        if ($this->_mode == BunnyPHP::MODE_NORMAL) {
            $this->assign('tp_user', $tp_user);
        }
        $this->render('pay/balance.html');
    }

    /**
     * @filter auth canPay
     * @filter csrf
     */
    public function ac_start_get()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        if (intval((new CreditModel())->balance($tp_user['uid'])) == -1) {
            $this->assign('csrf_token', BunnyPHP::app()->get('csrf_token'));
            $this->assign('tp_user', $tp_user);
            $this->render('pay/start.html');
        } else {
            $this->redirect('pay', 'balance');
        }
    }

    /**
     * @filter auth canPay
     * @filter csrf check
     * @param EmailService $service
     */
    public function ac_start_post(EmailService $service)
    {
        if (isset($_POST['pass'])) {
            $tp_user = BunnyPHP::app()->get('tp_user');
            $credit = new CreditModel();
            (new PayPassModel())->setPassword($tp_user['uid'], md5($_POST['pass']));
            $this->assign('ret', 0);
            $this->assign('status', 'ok');
            if ($this->_mode == BunnyPHP::MODE_NORMAL) {
                $this->assign('tp_user', $tp_user);
            }
            $this->assign('credit', $credit->start($tp_user['uid']));
            $service->sendMail('email/pay_start.html', ['nickname' => $tp_user['nickname'], 'site' => TP_SITE_NAME], $tp_user['email'], '您已开通' . TP_SITE_NAME . "支付服务");
            $this->render('pay/start.html');
        } else {
            $this->assign('ret', 1004)->assign('status', 'empty arguments')->assign('tp_error_msg', "必要参数为空")
                ->render('common/error.html');
        }
    }
}