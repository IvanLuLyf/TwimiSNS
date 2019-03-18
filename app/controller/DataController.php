<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/3/15
 * Time: 16:42
 */

class DataController extends Controller
{
    /**
     * @filter auth canGetInfo
     */
    public function ac_get()
    {
        $tp_api = BunnyPHP::app()->get('tp_api');
        $tp_user = BunnyPHP::app()->get('tp_user');
        $content = (new DataModel())->get($tp_user['uid'], $tp_api['id']);
        if ($content == null) $content = "";
        $this->assign('ret', 0)->assign('status', 'ok')->assign('content', $content)->render();
    }

    /**
     * @filter auth canGetInfo
     */
    public function ac_set()
    {
        $tp_api = BunnyPHP::app()->get('tp_api');
        $tp_user = BunnyPHP::app()->get('tp_user');
        (new DataModel())->set($tp_user['uid'], $tp_api['id'], $_POST['content']);
        $this->assign('ret', 0)->assign('status', 'ok')->render();
    }
}