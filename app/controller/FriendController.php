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

    /**
     * @param string $username not_empty()
     * @param string $notename not_empty()
     * @return array
     */
    public function ac_note(string $username, string $notename): array
    {
        return $this->friendModel->noteFriend($this->user['uid'], $username, $notename);
    }

    /**
     * @param UserModel $userModel
     * @param string $username not_empty()
     * @return array
     */
    public function ac_add(UserModel $userModel, string $username): array
    {
        $user = $userModel->getUserByUid($this->user['uid']);
        $f_user = $userModel->getUserByUsername($username);
        return $this->friendModel->addFriend($user['uid'], $f_user['uid'], $user['username'], $f_user['username'], $user['nickname'], $f_user['nickname']);
    }

    /**
     * @param UserModel $userModel
     * @param string $username not_empty()
     * @return array
     */
    public function ac_accept(UserModel $userModel, string $username): array
    {
        $user = $userModel->getUserByUid($this->user['uid']);
        $f_user = $userModel->getUserByUsername($username);
        return $this->friendModel->acceptFriend($user['uid'], $f_user['uid'], $user['username'], $f_user['username']);
    }
}