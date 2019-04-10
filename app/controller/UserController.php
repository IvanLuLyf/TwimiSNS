<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/7/28
 * Time: 18:43
 */

class UserController extends Controller
{
    /**
     * @filter csrf
     * @param $referer
     */
    public function ac_login_get($referer)
    {
        if ($referer) {
            BunnyPHP::getRequest()->setSession('referer', $referer);
            $this->assign('referer', $referer);
        }
        if ($this->_mode == BunnyPHP::MODE_NORMAL) {
            $oauth = [];
            if (Config::check("oauth")) {
                $oauth = Config::load('oauth')->get('enabled', []);
            }
            $this->assign('oauth', $oauth);
        }
        $this->render("user/login.html");
    }

    /**
     * @filter csrf check
     * @filter api
     * @param $referer
     */
    public function ac_login_post($referer)
    {
        $result = (new UserModel())->login($_POST['username'], $_POST['password']);
        if ($this->_mode == BunnyPHP::MODE_NORMAL) {
            if ($result['ret'] == 0) {
                BunnyPHP::getRequest()->setSession('token', $result['token']);
                $refererUrl = BunnyPHP::getRequest()->delSession('referer');
                $refererUrl = $referer ? $referer : $refererUrl;
                if ($refererUrl) {
                    $this->redirect($refererUrl);
                } else {
                    $this->redirect('index', 'index');
                }
            } else {
                $this->assignAll($result);
                $oauth = [];
                if (Config::check("oauth")) {
                    $oauth = Config::load('oauth')->get('enabled', []);
                }
                $this->assign('oauth', $oauth)->render('user/login.html');
            }
        } elseif ($this->_mode == BunnyPHP::MODE_API) {
            if ($result['ret'] == 0) {
                $app = BunnyPHP::app()->get('tp_api');
                $appToken = (new OauthTokenModel())->get($result['uid'], $_POST['client_id'], $app['type']);
                $result['token'] = $appToken['token'];
                $result['expire'] = $appToken['expire'];
                if (isset($appToken['refresh_token'])) {
                    $result['refresh_token'] = $appToken['refresh_token'];
                }
            }
            $this->assignAll($result)->render();
        }
    }

    /**
     * @filter csrf
     * @param $referer
     */
    public function ac_register_get($referer)
    {
        if (Config::load('config')->get('allow_reg')) {
            if ($referer) {
                BunnyPHP::getRequest()->setSession('referer', $referer);
                $this->assign('referer', $referer);
            }
            $this->render("user/register.html");
        } else {
            $this->assignAll(['ret' => 1007, 'status' => 'register not allowed', 'tp_error_msg' => '站点关闭注册'])->error();
        }
    }

    /**
     * @filter csrf check
     * @filter api
     * @param $referer
     */
    public function ac_register_post($referer)
    {
        if (Config::load('config')->get('allow_reg')) {
            $result = (new UserModel())->register($_POST['username'], $_POST['password'], $_POST['email'], $_POST['nickname']);
            if ($this->_mode == BunnyPHP::MODE_NORMAL) {
                if ($result['ret'] == 0) {
                    $service = new EmailService();
                    $service->sendMail('email/reg.html', ['nickname' => $result['nickname'], 'site' => TP_SITE_NAME], $result['email'], '欢迎注册' . TP_SITE_NAME);
                    BunnyPHP::getRequest()->setSession('token', $result['token']);
                    $refererUrl = BunnyPHP::getRequest()->delSession('referer');
                    $refererUrl = $referer ? $referer : $refererUrl;
                    if ($refererUrl) {
                        $this->redirect($refererUrl);
                    } else {
                        $this->redirect('index', 'index');
                    }
                } else {
                    $this->assignAll($result)->render('user/register.html');
                }
            } elseif ($this->_mode == BunnyPHP::MODE_API) {
                if ($result['ret'] == 0) {
                    $service = new EmailService();
                    $service->sendMail('email/reg.html', ['nickname' => $result['nickname'], 'site' => TP_SITE_NAME], $result['email'], '欢迎注册' . TP_SITE_NAME);
                    $app = BunnyPHP::app()->get('tp_api');
                    $appToken = (new OauthTokenModel())->get($result['uid'], $_POST['client_id'], $app['type']);
                    $result['token'] = $appToken['token'];
                    $result['expire'] = $appToken['expire'];
                    if (isset($appToken['refresh_token'])) {
                        $result['refresh_token'] = $appToken['refresh_token'];
                    }
                }
                $this->assignAll($result)->render();
            }
        } else {
            $this->assignAll(['ret' => 1007, 'status' => 'register not allowed', 'tp_error_msg' => '站点关闭注册'])->error();
        }
    }

    /**
     * @filter csrf
     */
    public function ac_forgot_get()
    {
        $this->render('user/forgot.html');
    }

    /**
     * @filter csrf check
     * @filter api
     */
    public function ac_forgot_post()
    {
        if (isset($_POST['email'])) {
            if ($user = (new UserModel())->where('username = :u or email = :u', ['u' => $_POST['email']])->fetch()) {
                $service = new EmailService();
                $code = (new PassCodeModel())->getCode($user['uid']);
                $service->sendMail('email/forgot.html', ['nickname' => $user['nickname'], 'site' => TP_SITE_NAME, 'url' => TP_SITE_URL, 'code' => $code], $user['email'], '找回密码');
                $this->assignAll(['ret' => 0, 'status' => 'ok', 'tp_error_msg' => "邮件已发送"])->render('common/error.html');
            } else {
                $this->assignAll(['ret' => 1002, 'status' => "user not exists", 'tp_error_msg' => '用户名不存在'])->render('user/forgot.html');
            }
        } else {
            $this->assignAll(['ret' => 1004, 'status' => 'empty arguments', 'tp_error_msg' => '必要参数为空'])->render('user/forgot.html');
        }
    }

    /**
     * @filter csrf
     */
    public function ac_reset_get()
    {
        if (isset($_REQUEST['code'])) {
            $this->assign('code', $_REQUEST['code'])->render('user/reset.html');
        } else {
            $this->assignAll(['ret' => 1004, 'status' => 'empty arguments', 'tp_error_msg' => '必要参数为空'])->error();
        }
    }

    /**
     * @filter csrf check
     * @filter api
     */
    public function ac_reset_post()
    {
        if (isset($_POST['code'])) {
            $uid = (new PassCodeModel())->checkCode($_POST['code']);
            if ($uid != null) {
                (new UserModel())->reset($uid, $_POST['password']);
                $this->assignAll(['ret' => 0, 'status' => 'ok', 'tp_error_msg' => "密码修改完成"])->render('common/error.html');
            } else {
                $this->assignAll(['ret' => 1008, 'status' => 'expired', 'tp_error_msg' => '验证码已过期'])->error();
            }
        } else {
            $this->assignAll(['ret' => 1004, 'status' => 'empty arguments', 'tp_error_msg' => '必要参数为空'])->error();
        }
    }

    public function ac_logout()
    {
        BunnyPHP::getRequest()->delSession('token');
        $this->redirect('user', 'login');
    }

    public function ac_avatar_get(array $path, $username)
    {
        if (count($path) == 0) $path = [0];
        $uid = isset($_GET['uid']) ? $_GET['uid'] : $path[0];
        $imgUrl = "/static/img/avatar.png";
        if ($username != null) {
            if ($uid = (new UserModel())->where(["username = :username"], ['username' => $username])->fetch()['uid']) {
                $imgUrl = (new AvatarModel())->getAvatar($uid);
            }
        } else if ($uid != 0) {
            $imgUrl = (new AvatarModel())->getAvatar($uid);
        }
        $this->redirect($imgUrl);
    }

    /**
     * @filter ajax
     * @filter api
     * @filter auth
     */
    public function ac_avatar_post()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $this->assign('tp_user', $tp_user);
        if (isset($_FILES['avatar'])) {
            $image_type = ['image/bmp', 'image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'application/x-bmp', 'application/x-jpg', 'application/x-png'];
            if (in_array($_FILES["avatar"]["type"], $image_type) && ($_FILES["avatar"]["size"] < 2000000)) {
                $t = time() % 1000;
                $url = $this->storage()->upload("avatar/" . $tp_user['uid'] . '_' . $t . ".jpg", $_FILES["avatar"]["tmp_name"]);
                (new AvatarModel())->upload($tp_user['uid'], $url);
                $response = ['ret' => 0, 'status' => 'ok', 'url' => $url];
            } else {
                $response = ['ret' => 1007, 'status' => 'wrong file'];
            }
            $this->assignAll($response);
        }
        if ($this->_mode == BunnyPHP::MODE_NORMAL) {
            $this->redirect('setting', 'avatar');
        } else {
            $this->render('setting/avatar.html');
        }
    }

    /**
     * @filter auth canGetInfo
     */
    public function ac_info()
    {
        if ($this->_mode == BunnyPHP::MODE_API) {
            $username = isset($_REQUEST['username']) ? $_REQUEST['username'] : '';
            $id_code = isset($_REQUEST['id_code']) ? $_REQUEST['id_code'] : '';
            if ($username == '' && $id_code == '') {
                $tp_user = BunnyPHP::app()->get('tp_user');
                $this->assignAll(['ret' => 0, 'status' => 'ok']);
                $this->assignAll($tp_user);
            } else if ($id_code != '') {
                $uid = (new IdCodeModel())->getIdByCode($id_code);
                if ($uid != 0) {
                    $response = (new UserModel())->getUserByUid($uid);
                    $this->assignAll(['ret' => 0, 'status' => 'ok']);
                    $this->assignAll($response);
                } else {
                    $this->assignAll(['ret' => 1008, 'status' => 'invalid id code']);
                }
            } else {
                $row = (new UserModel())->getUserByUsername($username);
                if ($row['uid'] != null) {
                    $this->assignAll(['ret' => 0, 'status' => 'ok']);
                    $this->assignAll($row);
                } else {
                    $this->assignAll(['ret' => 1005, 'status' => 'invalid username']);
                }
            }
        }
        $this->render('user/info.html');
    }

    /**
     * @filter auth canGetInfo
     */
    public function ac_code()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $code = (new IdCodeModel())->getIdCode($tp_user['uid']);
        $this->assignAll(['ret' => 0, 'status' => 'ok', 'code' => $code])->render();
    }

    public function ac_detail(array $path, UserService $userService)
    {
        if (count($path) == 0) $path = ['', ''];
        $username = isset($_GET['username']) ? $_GET['username'] : $path[0];
        $tab = isset($_GET['tab']) ? $_GET['tab'] : (isset($path[1]) ? $path[1] : 'post');

        $tp_user = $userService->getLoginUser();
        if ($username == '') {
            if ($tp_user == null) {
                $this->redirect('user', 'login', ['referer' => View::get_url('user', 'detail')]);
                return;
            }
            $username = $tp_user['username'];
        }
        $user = (new UserModel())->where(["username = :username"], ['username' => $username])->fetch(['uid', 'username', 'nickname']);
        $user_info = (new UserInfoModel())->get($user['uid']);

        switch ($tab) {
            case 'post':
                $posts = (new PostModel())->getPostByUsername($username);
                $this->assign('posts', $posts);
                break;
        }
        $this->assign('tab', $tab);
        $this->assign('user', $user);
        $this->assign('user_info', $user_info);
        $this->render('user/detail.html');
    }
}