<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/7/28
 * Time: 18:17
 */

class IndexController extends Controller
{
    function ac_index()
    {
        if (Config::check('config')) {
            $this->redirect('post', 'list');
        } else {
            $this->redirect('install', 'index');
        }
    }
}