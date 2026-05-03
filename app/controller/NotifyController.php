<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Controller;

/**
 * @author IvanLu
 * @time 2026/05/03 15:33
 */
class NotifyController extends Controller
{
    /**
     * @filter auth feed
     */
    function ac_view()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $notice = (new NotificationModel())->getNotice($tp_user['uid']);
        $this->assignAll(['ret' => 0, 'status' => 'ok', 'notifications' => $notice])->render('app.php');
    }
}