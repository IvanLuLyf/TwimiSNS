<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Controller;

/**
 * @author IvanLu
 * @time 2019/2/18 2:14
 * @filter auth friend
 */
class FriendController extends Controller
{
    private $user;
    private FriendModel $friendModel;

    public function __construct(FriendModel $friendModel)
    {
        $this->user = BunnyPHP::app()->get('tp_user');
        $this->friendModel = $friendModel;
    }

    public function ac_list($state = FriendModel::STATE_FRIEND): array
    {
        $friends = $this->friendModel->listFriend($this->user['uid'], $state);
        return ['ret' => 0, 'status' => 'ok', 'friends' => $friends];
    }

    public function ac_note(): array
    {
        if (StrUtil::emptyText($_POST['username']) || StrUtil::emptyText($_POST['notename'])) {
            return ['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '必要参数为空'];
        }
        return $this->friendModel->noteFriend($this->user['uid'], $_POST['username'], $_POST['notename']);
    }

    public function ac_add(UserModel $userModel): array
    {
        if (StrUtil::emptyText($_POST['username'])) {
            return ['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '必要参数为空'];
        }
        $user = $userModel->getUserByUid($this->user['uid']);
        $f_user = $userModel->getUserByUsername($_POST['username']);
        return $this->friendModel->addFriend($user['uid'], $f_user['uid'], $user['username'], $f_user['username'], $user['nickname'], $f_user['nickname']);
    }


    public function ac_accept(UserModel $userModel): array
    {
        if (StrUtil::emptyText($_POST['username'])) {
            return ['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '必要参数为空'];
        }
        $user = $userModel->getUserByUid($this->user['uid']);
        $f_user = $userModel->getUserByUsername($_POST['username']);
        return $this->friendModel->acceptFriend($user['uid'], $f_user['uid'], $user['username'], $f_user['username']);
    }
}