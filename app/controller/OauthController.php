<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Config;
use BunnyPHP\Controller;
use BunnyPHP\Request;

/**
 * @author IvanLu
 * @time 2018/7/30 16:27
 */
class OauthController extends Controller
{
    /**
     * @param string $type path(0)
     * @param string $referer
     */
    function ac_connect(string $type = '', string $referer = '')
    {
        $ref = trim((string)($_GET['referer'] ?? $referer));
        $ref = self::normalizeOAuthReturnTarget($ref);
        if ($ref !== '') {
            Request::session('referer', $ref);
        } else {
            Request::session('referer', null);
        }
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
        $this->redirect($url);
    }

    /**
     * 仅允许站内路径；禁止回到登录/注册/忘记密码页；支持从 `/user/login?referer=/foo` 解析真实去向。
     *
     * @return non-empty-string|''
     */
    private static function normalizeOAuthReturnTarget(string $raw, int $depth = 0): string
    {
        if ($depth > 3) {
            return '';
        }
        $raw = trim($raw);
        if ($raw === '' || ($raw[0] ?? '') !== '/') {
            return '';
        }
        $path = parse_url($raw, PHP_URL_PATH);
        if ($path === false || $path === null) {
            return '';
        }
        $path = $path === '' ? '/' : $path;
        $blockedPrefixes = ['/user/login', '/user/register', '/user/forgot'];
        foreach ($blockedPrefixes as $p) {
            if ($path === $p || strpos($path, $p . '/') === 0) {
                $q = parse_url($raw, PHP_URL_QUERY);
                if (is_string($q) && $q !== '') {
                    parse_str($q, $parts);
                    if (!empty($parts['referer'])) {
                        return self::normalizeOAuthReturnTarget(rawurldecode((string)$parts['referer']), $depth + 1);
                    }
                }

                return '';
            }
        }

        return $raw;
    }

    /**
     * @param UserService $userService
     * @param OauthService $oauthService
     * @param string $code not_empty()
     * @param string $type path(0)
     */
    function ac_callback(UserService $userService, OauthService $oauthService, string $code, string $type = '')
    {
        $bind_model = new BindModel();
        $bind = $oauthService->oauth($type, $code);
        if ($uid = $bind_model->getUid($bind['uid'], $type)) {
            $userToken = (new UserModel())->refresh($uid);
            $userService->setLoginUser($userToken);
            $bind_model->where(['bind=:b and type=:t'], ['b' => $bind['uid'], 't' => $type])->update(['token' => $bind['token'], 'expire' => $bind['expire']]);
            (new UserModel())->maybeSyncOauthAvatar((int)$uid);
            $referer = Request::session('referer', null);
            $referer = is_string($referer) ? self::normalizeOAuthReturnTarget($referer) : '';
            Request::session('referer', null);
            if ($referer !== '') {
                $this->redirect($referer);
            } else {
                $this->redirect('index', 'index');
            }
        } else {
            if ($user = $userService->getLoginUser()) {
                $bind_data = ['uid' => $user['uid'], 'type' => $type, 'bind' => $bind['uid'], 'token' => $bind['token'], 'expire' => $bind['expire']];
                $bind_model->add($bind_data);
                (new UserModel())->maybeSyncOauthAvatar((int)$user['uid']);
                $this->redirect('setting', 'oauth', ['type' => $type]);
            } else {
                Request::session('oauth_user', ['type' => $type, 'uid' => $bind['uid'], 'token' => $bind['token'], 'expire' => $bind['expire'], 'nickname' => $bind['nickname'],]);
                $allowReg = (bool)Config::load('config')->get('allow_reg');
                try {
                    $avatarUrl = $oauthService->avatar($type, (string)$bind['uid'], (string)($bind['token'] ?? ''));
                } catch (Throwable $e) {
                    $avatarUrl = '';
                }
                $this->assign('oauthBind', [
                    'type' => $type,
                    'nickname' => $bind['nickname'],
                    'allowReg' => $allowReg,
                    'avatarUrl' => $avatarUrl,
                ])->render('app.php');
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
            $appToken = (new OauthTokenModel())->generate($uid, $_POST['client_id'], 1);
            $result['token'] = $appToken['token'];
            $result['expire'] = $appToken['expire'];
            $result['refresh_token'] = $appToken['refresh_token'];
            $this->assign('ret', 0)->assign('status', 'ok')->assignAll($result)->render();
        } else {
            $this->assignAll(['ret' => 1002, 'status' => "user does not exist"])->render();
        }
    }

    /**
     * @filter csrf check
     * @param string $type path(0)
     */
    function ac_bind(string $type = '', string $bind_type = 'login')
    {
        if ($bind_type == 'reg' && Config::load('config')->get('allow_reg')) {
            $result = (new UserModel())->register($_POST['username'], $_POST['password'], $_POST['email'], $_POST['nickname']);
        } else {
            $result = (new UserModel())->login($_POST['username'], $_POST['password']);
        }
        if ($result['ret'] == 0) {
            Request::session('access_token', $result['token']);
            $bind = Request::session('oauth_user');
            $bind_data = ['uid' => $result['uid'], 'type' => $type, 'bind' => $bind['uid'], 'token' => $bind['token'], 'expire' => $bind['expire']];
            (new BindModel())->add($bind_data);
            (new UserModel())->maybeSyncOauthAvatar((int)$result['uid']);
            $referer = Request::session('referer', null);
            $referer = is_string($referer) ? self::normalizeOAuthReturnTarget($referer) : '';
            Request::session('referer', null);
            if ($referer !== '') {
                $this->redirect($referer);
            } else {
                $this->redirect('index', 'index');
            }
        } else {
            $allowReg = (bool)Config::load('config')->get('allow_reg');
            $this->assign('oauthBind', [
                'type' => $type,
                'nickname' => $_POST['nickname'] ?? '',
                'allowReg' => $allowReg,
                'errorMsg' => $result['tp_error_msg'] ?? ($result['status'] ?? ''),
                'bindMode' => $bind_type === 'reg' ? 'reg' : 'login',
            ])->render('app.php');
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
                $this->renderOAuthAuthorizeForm($userService, $app, $_REQUEST['client_id'], $_REQUEST['redirect_uri'], null);
            } else {
                $this->renderOAuthAuthorizeError('非法的应用网址');
            }
        } else {
            $this->renderOAuthAuthorizeError('非法的Client ID');
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
                            $userService->setLoginUser($result['token']);
                            $url = $_REQUEST['redirect_uri'];
                            $code = (new OauthCodeModel())->getCode($_REQUEST['client_id'], $app['id'], $result['uid'], time());
                            if (strpos($url, "?"))
                                $this->redirect("$url&code=$code");
                            else
                                $this->redirect("$url?code=$code");
                        } else {
                            $this->renderOAuthAuthorizeForm(
                                $userService,
                                $app,
                                $_REQUEST['client_id'],
                                $_REQUEST['redirect_uri'],
                                $result['tp_error_msg'] ?? null,
                            );
                        }
                    } else {
                        $this->renderOAuthAuthorizeForm($userService, $app, $_REQUEST['client_id'], $_REQUEST['redirect_uri'], null);
                    }
                }
            } else {
                $this->renderOAuthAuthorizeError('非法的应用网址');
            }
        } else {
            $this->renderOAuthAuthorizeError('非法的Client ID');
        }
    }

    private function renderOAuthAuthorizeError(string $msg): void
    {
        $this->assign('oauthAuthorize', [
            'hideForm' => true,
            'errorMsg' => $msg,
            'loggedIn' => false,
            'user' => null,
            'clientId' => '',
            'redirectUri' => '',
            'appUrl' => '',
            'clientName' => '',
            'clientIcon' => '',
        ])->render('app.php');
    }

    /**
     * @param array<string,mixed> $app
     */
    private function renderOAuthAuthorizeForm(UserService $userService, array $app, string $clientId, string $redirectUri, ?string $errorMsg): void
    {
        $tp_user = $userService->getLoginUser();
        $this->assign('oauthAuthorize', [
            'hideForm' => false,
            'errorMsg' => $errorMsg,
            'loggedIn' => $tp_user !== null,
            'user' => $tp_user ? UserController::slicePublic($tp_user) : null,
            'clientId' => $clientId,
            'redirectUri' => $redirectUri,
            'appUrl' => $app['url'],
            'clientName' => $app['name'],
            'clientIcon' => $app['icon'],
        ])->render('app.php');
    }

    public function ac_token_post()
    {
        if (isset($_REQUEST['client_id']) && isset($_REQUEST['client_secret']) && $app = (new ApiModel())->validate($_REQUEST['client_id'], $_REQUEST['client_secret'])) {
            $app_key = $_REQUEST['client_id'];
            $app_id = $app['id'];
            $oauthCodeModel = new OauthCodeModel();
            if (isset($_REQUEST['code']) && $uid = $oauthCodeModel->checkCode($app_id, $_REQUEST['code'])) {
                $token_row = (new OauthTokenModel())->generate($uid, $app_key, $app['type']);
                $this->assignAll(['ret' => 0, 'status' => 'ok'])->assignAll($token_row);
                $oauthCodeModel->deleteCode($app_id, $_REQUEST['code']);
                $this->render('app.php');
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