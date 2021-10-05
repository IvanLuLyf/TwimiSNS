<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Controller;

/**
 * User: IvanLu
 * Date: 2019/2/18
 * Time: 2:14
 */
class FriendController extends Controller
{
    private FriendModel $friendModel;

    public function __construct(FriendModel $friendModel)
    {
        $this->friendModel = $friendModel;
    }

    /**
     * @filter auth canGetFriend
     */
    public function ac_list()
    {
        $state = $_REQUEST['state'] ?? 2;
        $tp_user = BunnyPHP::app()->get('tp_user');
        $friends = $this->friendModel->listFriend($tp_user['uid'], $state);
        return ['ret' => 0, 'status' => 'ok', 'friends' => $friends];
    }

    /**
     * @filter auth canGetFriend
     */
    public function ac_note()
    {
        if (!isset($_POST['username']) || !isset($_POST['notename'])) {
            return ['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '必要参数为空'];
        }
        $tp_user = BunnyPHP::app()->get('tp_user');
        return $this->friendModel->noteFriend($tp_user['uid'], $_POST['username'], $_POST['notename']);
    }

    /**
     * @filter auth canGetFriend
     */
    public function ac_add(UserModel $userModel)
    {
        if (!isset($_POST['username'])) {
            return ['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '必要参数为空'];
        }
        $tp_user = BunnyPHP::app()->get('tp_user');
        $user = $userModel->getUserByUid($tp_user['uid']);
        $f_user = $userModel->getUserByUsername($_POST['username']);
        return $this->friendModel->addFriend($user['uid'], $f_user['uid'], $user['username'], $f_user['username'], $user['nickname'], $f_user['nickname']);
    }

    /**
     * @filter auth canGetFriend
     */
    public function ac_accept(UserModel $userModel)
    {
        if (!isset($_POST['username'])) {
            return ['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '必要参数为空'];
        }
        $tp_user = BunnyPHP::app()->get('tp_user');
        $user = $userModel->getUserByUid($tp_user['uid']);
        $f_user = $userModel->getUserByUsername($_POST['username']);
        return $this->friendModel->acceptFriend($user['uid'], $f_user['uid'], $user['username'], $f_user['username']);
    }
}