<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Controller;

/**
 * @author IvanLu
 * @time 2026/5/3 16:32
 * @filter auth friend
 */
class FriendController extends Controller
{
    private FriendModel $friendModel;

    public function __construct(FriendModel $friendModel)
    {
        $this->friendModel = $friendModel;
    }

    public function ac_index(): void
    {
        $this->render('app.php');
    }

    public function ac_json_get(UserModel $userModel): void
    {
        $u = BunnyPHP::app()->get('tp_user');
        $uid = (int)$u['uid'];
        $this->assignAll([
            'ret' => 0,
            'status' => 'ok',
            'friends' => $this->enrichFriendRows($this->friendModel->listFriend($uid, FriendModel::STATE_FRIEND), $userModel),
            'pending_in' => $this->enrichFriendRows($this->friendModel->listFriend($uid, FriendModel::STATE_PENDING), $userModel),
            'pending_out' => $this->enrichFriendRows($this->friendModel->listFriend($uid, FriendModel::STATE_REQUEST), $userModel),
        ])->render('app.php');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function enrichFriendRows(array $rows, UserModel $userModel): array
    {
        if ($rows === []) {
            return [];
        }
        $map = $userModel->getPublicByUsernames(array_column($rows, 'username'));
        foreach ($rows as &$r) {
            $pu = $map[$r['username']] ?? [];
            $r['nickname'] = (string)($pu['nickname'] ?? '');
        }
        unset($r);

        return $rows;
    }

    /**
     * @filter csrf check
     */
    public function ac_json_add_post(UserService $userService, UserModel $userModel): array
    {
        $u = $userService->getLoginUser();
        $username = trim((string)($_POST['username'] ?? ''));
        if ($username === '') {
            return ['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '请填写用户名'];
        }
        $me = $userModel->getUserByUid($u['uid']);
        $f_user = $userModel->getUserByUsername($username);
        if (!is_array($me) || !is_array($f_user) || empty($me['uid']) || empty($f_user['uid'])) {
            return ['ret' => 1004, 'status' => 'invalid username', 'tp_error_msg' => '用户不存在'];
        }

        return $this->friendModel->addFriend(
            (int)$me['uid'],
            (int)$f_user['uid'],
            (string)$me['username'],
            (string)$f_user['username'],
            (string)($me['nickname'] ?? ''),
            (string)($f_user['nickname'] ?? ''),
        );
    }

    /**
     * @filter csrf check
     */
    public function ac_json_accept_post(UserService $userService, UserModel $userModel): array
    {
        $u = $userService->getLoginUser();
        $username = trim((string)($_POST['username'] ?? ''));
        if ($username === '') {
            return ['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '请填写用户名'];
        }
        $me = $userModel->getUserByUid($u['uid']);
        $f_user = $userModel->getUserByUsername($username);
        if (!is_array($me) || !is_array($f_user) || empty($me['uid']) || empty($f_user['uid'])) {
            return ['ret' => 1004, 'status' => 'invalid username', 'tp_error_msg' => '用户不存在'];
        }

        return $this->friendModel->acceptFriend(
            (int)$me['uid'],
            (int)$f_user['uid'],
            (string)$me['username'],
            (string)$f_user['username'],
        );
    }

    public function ac_list($state = FriendModel::STATE_FRIEND): array
    {
        $user = BunnyPHP::app()->get('tp_user');
        $friends = $this->friendModel->listFriend($user['uid'], $state);

        return ['ret' => 0, 'status' => 'ok', 'friends' => $friends];
    }

    /**
     * @param string $username not_empty()
     * @param string $remark not_empty()
     * @return array
     */
    public function ac_remark(string $username, string $remark): array
    {
        $user = BunnyPHP::app()->get('tp_user');

        return $this->friendModel->remarkFriend($user['uid'], $username, $remark);
    }

    /**
     * @param UserModel $userModel
     * @param string $username not_empty()
     * @return array
     */
    public function ac_add(UserModel $userModel, string $username): array
    {
        $user = BunnyPHP::app()->get('tp_user');
        $me = $userModel->getUserByUid($user['uid']);
        $f_user = $userModel->getUserByUsername($username);
        if (!is_array($me) || !is_array($f_user) || empty($me['uid']) || empty($f_user['uid'])) {
            return ['ret' => 1004, 'status' => 'invalid username'];
        }

        return $this->friendModel->addFriend($me['uid'], $f_user['uid'], $me['username'], $f_user['username'], $me['nickname'], $f_user['nickname']);
    }

    /**
     * @param UserModel $userModel
     * @param string $username not_empty()
     * @return array
     */
    public function ac_accept(UserModel $userModel, string $username): array
    {
        $user = BunnyPHP::app()->get('tp_user');
        $me = $userModel->getUserByUid($user['uid']);
        $f_user = $userModel->getUserByUsername($username);
        if (!is_array($me) || !is_array($f_user) || empty($me['uid']) || empty($f_user['uid'])) {
            return ['ret' => 1004, 'status' => 'invalid username'];
        }

        return $this->friendModel->acceptFriend($me['uid'], $f_user['uid'], $me['username'], $f_user['username']);
    }
}
