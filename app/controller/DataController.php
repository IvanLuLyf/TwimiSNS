<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Controller;

/**
 * @author IvanLu
 * @time 2019/3/15 16:42
 * @filter auth canGetInfo
 */
class DataController extends Controller
{
    public function ac_get(DataModel $dataModel): array
    {
        $tp_api = BunnyPHP::app()->get('tp_api');
        $tp_user = BunnyPHP::app()->get('tp_user');
        $content = $dataModel->get($tp_user['uid'], $tp_api['id']);
        if ($content == null) $content = '';
        return ['ret' => 0, 'status' => 'ok', 'content' => $content];
    }

    public function ac_set(DataModel $dataModel): array
    {
        $tp_api = BunnyPHP::app()->get('tp_api');
        $tp_user = BunnyPHP::app()->get('tp_user');
        $dataModel->set($tp_user['uid'], $tp_api['id'], $_POST['content']);
        return ['ret' => 0, 'status' => 'ok'];
    }
}