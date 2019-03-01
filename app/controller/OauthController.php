<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/7/30
 * Time: 16:27
 */

class OauthController extends Controller
{
    function ac_connect(array $path)
    {
        $url = '/index';
        if (count($path) < 1) $path = [''];
        list($type) = $path;
        switch ($type) {
            case 'qq':
                $oauth = Config::load('oauth')->get('qq');
                $url = 'https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id=' . $oauth['key'] . '&redirect_uri=' . urlencode($oauth['callback']);
                break;
            case 'wb':
                $oauth = Config::load('oauth')->get('wb');
                $url = 'https://api.weibo.com/oauth2/authorize?client_id=' . $oauth['key'] . '&redirect_uri=' . urlencode($oauth['callback']) . '&response_type=code';
                break;
            case 'gh':
                $oauth = Config::load('oauth')->get('gh');
                $url = 'https://github.com/login/oauth/authorize?client_id=' . $oauth['key'] . '&redirect_uri=' . urlencode($oauth['callback']) . '&scope=user,public_repo';
                break;
            case 'tm':
                $oauth = Config::load('oauth')->get('tm');
                $url = 'http://tp.twimi.cn/index.php?mod=tauth&appkey=' . $oauth['key'] . '&url=' . urlencode($oauth['callback']);
                break;
        }
        if (isset($_REQUEST['referer'])) {
            if (!session_id()) session_start();
            $_SESSION['referer'] = $_REQUEST['referer'];
        }
        $this->redirect($url);
    }

    function ac_callback(array $path, UserService $userService)
    {
        if (count($path) < 1) $path = [''];
        list($type) = $path;
        $uid = null;
        if (isset($_GET['code'])) {
            $bind_model = new BindModel();
            $bind = (new OauthService($this))->oauth($type);
            if ($uid = $bind_model->getUid($bind['uid'], $type)) {
                $userToken = (new UserModel())->refresh($uid);
                if (!session_id()) session_start();
                $_SESSION['token'] = $userToken;
                $bind_model->where(['bind=:b and type=:t'], ['b' => $bind['uid'], 't' => $type])->update(['token' => $bind['token'], 'expire' => $bind['expire']]);
                if (isset($_SESSION['referer'])) {
                    $referer = $_SESSION['referer'];
                    unset($_SESSION['referer']);
                    $this->redirect($referer);
                } else {
                    $this->redirect('index', 'index');
                }
            } else {
                if ($user = $userService->getLoginUser()) {
                    $bind_data = ['uid' => $user['uid'], 'type' => $type, 'bind' => $bind['uid'], 'token' => $bind['token'], 'expire' => $bind['expire']];
                    $bind_model->add($bind_data);
                    $this->redirect('setting', 'oauth', ['type' => $type]);
                } else {
                    if (!session_id()) session_start();
                    $_SESSION['oauth_user'] = ['type' => $type, 'uid' => $bind['uid'], 'token' => $bind['token'], 'expire' => $bind['expire'], 'nickname' => $bind['nickname'],];
                    if (Config::load('config')->get('allow_reg')) {
                        $this->assign('allow_reg', true);
                    }
                    $this->assign('oauth', ['nickname' => $bind['nickname'], 'type' => $type])
                        ->render('oauth/connect.html');
                }
            }
        }
    }

    /**
     * @filter api
     */
    function ac_login()
    {
        $type = $_REQUEST['type'];
        $bind_uid = $_REQUEST['bind'];
        $bind_token = $_REQUEST['token'];
        $model = new BindModel();
        if ($uid = $model->getUid($bind_uid, $type)) {
            $model->where(['bind=:b and type=:t'], ['b' => $bind_uid, 't' => $type])->update(['token' => $bind_token]);
            $result = (new UserModel())->getUserByUid($uid);
            $appToken = (new OauthTokenModel())->get($uid, $_POST['appkey']);
            $result['token'] = $appToken['token'];
            $result['expire'] = $appToken['expire'];
            $this->assign('ret', 0)->assign('status', 'ok')->assignAll($result)->render();
        }
    }

    function ac_bind(array $path)
    {
        if (count($path) < 1) $path = [''];
        list($type) = $path;
        $bind_type = $_REQUEST['type'];
        if ($bind_type == 'reg' && Config::load('config')->get('allow_reg')) {
            $result = (new UserModel())->register($_POST['username'], $_POST['password'], $_POST['email'], $_POST['nickname']);
        } else {
            $result = (new UserModel())->login($_POST['username'], $_POST['password']);
        }
        if ($result['ret'] == 0) {
            if (!session_id()) session_start();
            $bind = $_SESSION['oauth_user'];
            $bind_data = ['uid' => $result['uid'], 'type' => $type, 'bind' => $bind['uid'], 'token' => $bind['token'], 'expire' => $bind['expire']];
            (new BindModel())->add($bind_data);
            if (isset($_SESSION['referer'])) {
                $referer = $_SESSION['referer'];
                unset($_SESSION['referer']);
                $this->redirect($referer);
            } else {
                $this->redirect('index', 'index');
            }
        } else {
            $this->assignAll($result);
            $this->assign('oauth', ['type' => $type, 'nickname' => isset($_POST['nickname']) ? $_POST['nickname'] : ''])
                ->render('oauth/connect.html');
        }
    }
}