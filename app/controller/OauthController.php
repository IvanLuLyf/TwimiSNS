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
            default:
                $oauth = Config::load('oauth')->get($type);
                $url = $oauth['url'] . '/oauth/authorize?client_id=' . $oauth['key'] . '&redirect_uri=' . urlencode($oauth['callback']);
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
        $bind_model = new BindModel();
        if (isset($_GET['code'])) {
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
            $appToken = (new OauthTokenModel())->get($uid, $_POST['client_id'], 1);
            $result['token'] = $appToken['token'];
            $result['expire'] = $appToken['expire'];
            $result['refresh_token'] = $appToken['refresh_token'];
            $this->assign('ret', 0)->assign('status', 'ok')->assignAll($result)->render();
        } else {
            $this->assignAll(['ret' => 1002, 'status' => "user not exists"])->render();
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

    /**
     * @filter csrf
     * @param UserService $userService
     */
    function ac_authorize_get(UserService $userService)
    {
        if (isset($_REQUEST['client_id']) && $app = (new ApiModel())->check($_REQUEST['client_id'])) {
            if (isset($_REQUEST['redirect_uri']) && strpos($_REQUEST['redirect_uri'], $app['redirect_uri']) === 0) {
                $tp_user = $userService->getLoginUser();
                $this->assign('tp_user', $tp_user)
                    ->assign('client_id', $_REQUEST['client_id'])
                    ->assign('app_url', $app['url'])
                    ->assign('client_name', $app['name'])
                    ->assign('redirect_uri', $_REQUEST['redirect_uri'])
                    ->assign('csrf_token', BunnyPHP::app()->get('csrf_token'));;
                $this->render('oauth/login.html');
            } else {
                $this->assign('tp_error_msg', '非法的应用网址')
                    ->assign('tp_hide', true);
                $this->render('oauth/login.html');
            }
        } else {
            $this->assign('tp_error_msg', '非法的Client ID')
                ->assign('tp_hide', true);
            $this->render('oauth/login.html');
        }
    }

    /**
     * @filter csrf check
     * @param UserService $userService
     */
    function ac_authorize_post(UserService $userService)
    {
        if (isset($_REQUEST['client_id']) && $app = (new ApiModel())->check($_REQUEST['client_id'])) {
            if (isset($_REQUEST['redirect_uri']) && strpos($_REQUEST['redirect_uri'], $app['redirect_uri']) === 0) {
                if (($user = $userService->getLoginUser()) != null) {
                    $url = $_REQUEST['redirect_uri'];
                    $code = (new OauthCodeModel())->getCode($_REQUEST['client_id'], $app['id'], $user['uid'], time());
                    if (strpos($url, "?"))
                        $this->redirect("$url&code=$code");
                    else
                        $this->redirect("$url?code=$code");
                } else {
                    if (isset($_POST['username']) && isset($_POST['password'])) {
                        $result = (new UserModel())->login($_POST['username'], $_POST['password']);
                        if ($result['ret'] == 0) {
                            if (!session_id()) session_start();
                            $_SESSION['token'] = $result['token'];
                            $url = $_REQUEST['redirect_uri'];
                            $code = (new OauthCodeModel())->getCode($_REQUEST['client_id'], $app['id'], $result['uid'], time());
                            if (strpos($url, "?"))
                                $this->redirect("$url&code=$code");
                            else
                                $this->redirect("$url?code=$code");
                        } else {
                            $this->assignAll($result);
                            $this->assign('csrf_token', BunnyPHP::app()->get('csrf_token'));
                            $this->render('oauth/login.html');
                        }
                    }
                }
            } else {
                $this->assign('tp_error_msg', '非法的应用网址')
                    ->assign('tp_hide', true);
                $this->render('oauth/login.html');
            }
        } else {
            $this->assign('tp_error_msg', '非法的Client ID')
                ->assign('tp_hide', true);
            $this->render('oauth/login.html');
        }
    }

    public function ac_token_post()
    {
        if (isset($_REQUEST['client_id']) && isset($_REQUEST['client_secret']) && $app = (new ApiModel())->validate($_REQUEST['client_id'], $_REQUEST['client_secret'])) {
            $app_key = $_REQUEST['client_id'];
            $app_id = $app['id'];
            $oauthCodeModel = new OauthCodeModel();
            if (isset($_REQUEST['code']) && $uid = $oauthCodeModel->checkCode($app_id, $_REQUEST['code'])) {
                $token_row = (new OauthTokenModel())->get($uid, $app_key, $app['type']);
                $this->assign('ret', 0)->assign('status', 'ok')->assignAll($token_row);
                $oauthCodeModel->deleteCode($app_id, $_REQUEST['code']);
                $this->render('common/error.html');
            } else {
                $this->assign('ret', 2005)->assign('status', 'invalid oauth code');
                $this->render('common/error.html');
            }
        } else {
            $this->assign('ret', 2001)->assign('status', 'invalid client id');
            $this->render('common/error.html');
        }
    }
}