<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Controller;

/**
 * User: IvanLu
 * Date: 2019/3/15
 * Time: 16:42
 */
class DataController extends Controller
{
    /**
     * @filter auth canGetInfo
     */
    public function ac_get(DataModel $dataModel)
    {
        $tp_api = BunnyPHP::app()->get('tp_api');
        $tp_user = BunnyPHP::app()->get('tp_user');
        $content = $dataModel->get($tp_user['uid'], $tp_api['id']);
        if ($content == null) $content = '';
        return ['ret' => 0, 'status' => 'ok', 'content' => $content];
    }

    /**
     * @filter auth canGetInfo
     */
    public function ac_set(DataModel $dataModel)
    {
        $tp_api = BunnyPHP::app()->get('tp_api');
        $tp_user = BunnyPHP::app()->get('tp_user');
        $dataModel->set($tp_user['uid'], $tp_api['id'], $_POST['content']);
        return ['ret' => 0, 'status' => 'ok'];
    }
}