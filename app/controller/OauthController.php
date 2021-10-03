<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Config;
use BunnyPHP\Controller;

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/7/30
 * Time: 16:27
 */
class OauthController extends Controller
{
    function ac_connect(array $path, $referer)
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
        if ($referer) {
            BunnyPHP::getRequest()->setSession('referer', $referer);
        }
        $this->redirect($url);
    }

    function ac_callback(array $path, UserService $userService)
    {
        if (count($path) < 1) $path = [''];
        list($type) = $path;
        $bind_model = new BindModel();
        if (isset($_GET['code'])) {
            $bind = (new OauthService($this))->oauth($type);
            if ($uid = $bind_model->getUid($bind['uid'], $type)) {
                $userToken = (new UserModel())->refresh($uid);
                BunnyPHP::getRequest()->setSession('token', $userToken);
                $bind_model->where(['bind=:b and type=:t'], ['b' => $bind['uid'], 't' => $type])->update(['token' => $bind['token'], 'expire' => $bind['expire']]);
                $referer = BunnyPHP::getRequest()->delSession('referer');
                if ($referer) {
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
                    BunnyPHP::getRequest()->setSession('oauth_user', ['type' => $type, 'uid' => $bind['uid'], 'token' => $bind['token'], 'expire' => $bind['expire'], 'nickname' => $bind['nickname'],]);
                    if (Config::load('config')->get('allow_reg')) {
                        $this->assign('allow_reg', true);
                    }
                    $this->assign('oauth', ['nickname' => $bind['nickname'], 'type' => $type])->render('oauth/connect.html');
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
            $this->assignAll(['ret' => 1002, 'status' => "user does not exist"])->render();
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
            BunnyPHP::getRequest()->setSession('access_token', $result['token']);
            $bind = BunnyPHP::getRequest()->getSession('oauth_user');
            $bind_data = ['uid' => $result['uid'], 'type' => $type, 'bind' => $bind['uid'], 'token' => $bind['token'], 'expire' => $bind['expire']];
            (new BindModel())->add($bind_data);
            $referer = BunnyPHP::getRequest()->delSession('referer');
            if ($referer) {
                $this->redirect($referer);
            } else {
                $this->redirect('index', 'index');
            }
        } else {
            $this->assignAll($result);
            $this->assign('oauth', ['type' => $type, 'nickname' => $_POST['nickname'] ?? ''])
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
                    ->assign('client_icon', $app['icon'])
                    ->assign('redirect_uri', $_REQUEST['redirect_uri']);
                $this->render('oauth/login.html');
            } else {
                $this->assignAll(['tp_error_msg' => '非法的应用网址', 'tp_hide' => true])->render('oauth/login.html');
            }
        } else {
            $this->assignAll(['tp_error_msg' => '非法的Client ID', 'tp_hide' => true])->render('oauth/login.html');
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
                            BunnyPHP::getRequest()->setSession('token', $result['token']);
                            $url = $_REQUEST['redirect_uri'];
                            $code = (new OauthCodeModel())->getCode($_REQUEST['client_id'], $app['id'], $result['uid'], time());
                            if (strpos($url, "?"))
                                $this->redirect("$url&code=$code");
                            else
                                $this->redirect("$url?code=$code");
                        } else {
                            $this->assignAll($result)->render('oauth/login.html');
                        }
                    }
                }
            } else {
                $this->assignAll(['tp_error_msg' => '非法的应用网址', 'tp_hide' => true])->render('oauth/login.html');
            }
        } else {
            $this->assignAll(['tp_error_msg' => '非法的Client ID', 'tp_hide' => true])->render('oauth/login.html');
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
                $this->assignAll(['ret' => 0, 'status' => 'ok'])->assignAll($token_row);
                $oauthCodeModel->deleteCode($app_id, $_REQUEST['code']);
                $this->render('common/error.html');
            } else {
                $this->assignAll(['ret' => 2004, 'status' => 'invalid oauth code'])->error();
            }
        } else {
            $this->assignAll(['ret' => 2001, 'status' => 'invalid client id'])->error();
        }
    }

    public function ac_refresh_post()
    {
        if (isset($_REQUEST['client_id']) && $app = (new ApiModel())->check($_REQUEST['client_id'])) {
            $app_key = $_REQUEST['client_id'];
            if (isset($_REQUEST['token']) && isset($_REQUEST['refresh_token'])) {
                $token_row = (new OauthTokenModel())->refresh($_REQUEST['token'], $app_key, $_REQUEST['refresh_token']);
                if ($token_row != null) {
                    $this->assignAll(['ret' => 0, 'status' => 'ok'])->assignAll($token_row)->render();
                } else {
                    $this->assignAll(['ret' => 2005, 'status' => 'invalid refresh token'])->error();
                }
            } else {
                $this->assignAll(['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '必要参数为空'])->error();
            }
        } else {
            $this->assignAll(['ret' => 2001, 'status' => 'invalid client id'])->error();
        }
    }

    /**
     * @filter auth
     */
    public function ac_info()
    {
        if (isset($_REQUEST['app_id']) && ($app = (new ApiModel())->check($_REQUEST['app_id']))) {
            $this->assignAll(['ret' => 0, 'status' => 'ok', 'name' => $app['name'], 'icon' => $app['icon']])->render();
        } else {
            $this->assignAll(['ret' => 2001, 'status' => 'invalid client id'])->render();
        }
    }

    /**
     * @filter auth
     */
    public function ac_code()
    {
        if (isset($_REQUEST['app_id']) && ($app = (new ApiModel())->check($_REQUEST['app_id']))) {
            $user = BunnyPHP::app()->get('tp_user');
            $timestamp = time();
            $code = (new OauthCodeModel())->getCode($_REQUEST['app_id'], $app['id'], $user['uid'], $timestamp);
            $this->assignAll(['ret' => 0, 'status' => 'ok', 'code' => $code, 'expire' => $timestamp + 604800])->render();
        } else {
            $this->assignAll(['ret' => 2001, 'status' => 'invalid client id'])->render();
        }
    }
}