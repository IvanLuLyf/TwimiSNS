<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/3/7
 * Time: 17:32
 */

class NotifyController extends Controller
{
    /**
     * @filter auth canFeed
     */
    function ac_view()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $this->assign('ret', 0);
        $this->assign('status', 'ok');
        $notice = (new NotificationModel())->getNotice($tp_user['uid']);
        $this->assign('notifications', $notice);
        $this->render();
    }
}