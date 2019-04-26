<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/2/18
 * Time: 2:14
 */

class FriendController extends Controller
{
    /**
     * @filter auth canGetFriend
     */
    function ac_list()
    {
        $state = isset($_REQUEST['state']) ? $_REQUEST['state'] : 2;
        $tp_user = BunnyPHP::app()->get('tp_user');
        $friends = (new FriendModel())->listFriend($tp_user['uid'], $state);
        if ($this->_mode == BunnyPHP::MODE_API) {
            $this->assignAll(['ret' => 0, 'status' => 'ok', 'friends' => $friends]);
        }
        $this->render();
    }

    /**
     * @filter auth canGetFriend
     */
    function ac_note()
    {
        if (isset($_POST['username']) && isset($_POST['notename'])) {
            $tp_user = BunnyPHP::app()->get('tp_user');
            if ($this->_mode == BunnyPHP::MODE_API) {
                $result = (new FriendModel())->noteFriend($tp_user['uid'], $_POST['username'], $_POST['notename']);
                $this->assignAll($result)->render();
            }
        } else {
            $this->assignAll(['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '必要参数为空'])->error();
        }
    }

    /**
     * @filter auth canGetFriend
     */
    function ac_add()
    {
        if (isset($_POST['username'])) {
            $tp_user = BunnyPHP::app()->get('tp_user');
            if ($this->_mode == BunnyPHP::MODE_API) {
                $user = (new UserModel())->getUserByUid($tp_user['uid']);
                $f_user = (new UserModel())->getUserByUsername($_POST['username']);
                $result = (new FriendModel())->addFriend($user['uid'], $f_user['uid'], $user['username'], $f_user['username'], $user['nickname'], $f_user['nickname']);
                $this->assignAll($result)->render();
            }
        } else {
            $this->assignAll(['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '必要参数为空'])->error();
        }
    }

    /**
     * @filter auth canGetFriend
     */
    function ac_accept()
    {
        if (isset($_POST['username'])) {
            $tp_user = BunnyPHP::app()->get('tp_user');
            if ($this->_mode == BunnyPHP::MODE_API) {
                $user = (new UserModel())->getUserByUid($tp_user['uid']);
                $f_user = (new UserModel())->getUserByUsername($_POST['username']);
                $result = (new FriendModel())->acceptFriend($user['uid'], $f_user['uid'], $user['username'], $f_user['username']);
                $this->assignAll($result)->render();
            }
        } else {
            $this->assignAll(['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '必要参数为空'])->error();
        }
    }
}