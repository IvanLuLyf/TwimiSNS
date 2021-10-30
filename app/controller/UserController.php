<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Config;
use BunnyPHP\Controller;
use BunnyPHP\Request;
use BunnyPHP\View;

/**
 * @author IvanLu
 * @time 2018/7/28 18:43
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
            Request::session('referer', $referer);
            $this->assign('referer', $referer);
        }
        if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
            $oauth = [];
            if (Config::check('oauth')) {
                $oauth = Config::load('oauth')->get('enabled', []);
            }
            $this->assign('oauth', $oauth);
        }
        $this->render("user/login.php");
    }

    /**
     * @filter csrf check
     * @filter api
     * @param $referer
     */
    public function ac_login_post($referer, UserService $userService)
    {
        $result = (new UserModel())->login($_POST['username'], $_POST['password']);
        if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
            if ($result['ret'] == 0) {
                $userService->setLoginUser($result['token']);
                $refererUrl = $referer ?: Request::session('referer', null);
                if ($refererUrl) {
                    $this->redirect($refererUrl);
                } else {
                    $this->redirect('index', 'index');
                }
            } else {
                $this->assignAll($result);
                $oauth = [];
                if (Config::check('oauth')) {
                    $oauth = Config::load('oauth')->get('enabled', []);
                }
                $this->assign('oauth', $oauth)->render('user/login.php');
            }
        } elseif (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
            if ($result['ret'] == 0) {
                $app = BunnyPHP::app()->get('tp_api');
                $appToken = (new OauthTokenModel())->generate($result['uid'], $_POST['client_id'], $app['type']);
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
                Request::session('referer', $referer);
                $this->assign('referer', $referer);
            }
            $this->render('user/register.php');
        } else {
            $this->assignAll(['ret' => 1005, 'status' => 'registration is not allowed', 'tp_error_msg' => '站点关闭注册'])->error();
        }
    }

    /**
     * @filter csrf check
     * @filter api
     * @param $referer
     */
    public function ac_register_post($referer, UserService $userService)
    {
        if (Config::load('config')->get('allow_reg')) {
            $result = (new UserModel())->register($_POST['username'], $_POST['password'], $_POST['email'], $_POST['nickname']);
            if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
                if ($result['ret'] == 0) {
                    $service = new EmailService();
                    $service->sendMail('email/reg.html', ['nickname' => $result['nickname'], 'site' => TP_SITE_NAME], $result['email'], '欢迎注册' . TP_SITE_NAME);
                    $userService->setLoginUser($result['token']);
                    $refererUrl = $referer ?: Request::session('referer', null);
                    if ($refererUrl) {
                        $this->redirect($refererUrl);
                    } else {
                        $this->redirect('index', 'index');
                    }
                } else {
                    $this->assignAll($result)->render('user/register.php');
                }
            } elseif (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
                if ($result['ret'] == 0) {
                    $service = new EmailService();
                    $service->sendMail('email/reg.html', ['nickname' => $result['nickname'], 'site' => TP_SITE_NAME], $result['email'], '欢迎注册' . TP_SITE_NAME);
                    $app = BunnyPHP::app()->get('tp_api');
                    $appToken = (new OauthTokenModel())->generate($result['uid'], $_POST['client_id'], $app['type']);
                    $result['token'] = $appToken['token'];
                    $result['expire'] = $appToken['expire'];
                    if (isset($appToken['refresh_token'])) {
                        $result['refresh_token'] = $appToken['refresh_token'];
                    }
                }
                $this->assignAll($result)->render();
            }
        } else {
            $this->assignAll(['ret' => 1005, 'status' => 'registration is not allowed', 'tp_error_msg' => '站点关闭注册'])->error();
        }
    }

    /**
     * @filter csrf
     */
    public function ac_forgot_get()
    {
        $this->render('user/forgot.php');
    }

    /**
     * @filter csrf check
     * @filter api
     * @param string $email not_empty()
     */
    public function ac_forgot_post(string $email)
    {
        if ($user = (new UserModel())->where('username = :u or email = :u', ['u' => $email])->fetch()) {
            $service = new EmailService();
            $code = (new PassCodeModel())->getCode($user['uid']);
            $service->sendMail('email/forgot.html', ['nickname' => $user['nickname'], 'site' => TP_SITE_NAME, 'url' => TP_SITE_URL, 'code' => $code], $user['email'], '找回密码');
            $this->assignAll(['ret' => 0, 'status' => 'ok', 'tp_error_msg' => "邮件已发送"])->render('common/error.php');
        } else {
            $this->assignAll(['ret' => 1002, 'status' => "user does not exist", 'tp_error_msg' => '用户名不存在'])->render('user/forgot.php');
        }
    }

    /**
     * @filter csrf
     * @param string $code not_empty()
     */
    public function ac_reset_get(string $code)
    {
        $this->assign('code', $code)->render('user/reset.php');
    }

    /**
     * @filter csrf check
     * @filter api
     * @param string $code not_empty()
     * @param string $password not_empty()
     */
    public function ac_reset_post(string $code, string $password)
    {
        $uid = (new PassCodeModel())->checkCode($code);
        if ($uid != null) {
            (new UserModel())->reset($uid, $password);
            $this->assignAll(['ret' => 0, 'status' => 'ok', 'tp_error_msg' => '密码修改完成'])->render('common/error.php');
        } else {
            $this->assignAll(['ret' => 1008, 'status' => 'invalid verification code', 'tp_error_msg' => '验证码已过期'])->error();
        }
    }

    public function ac_logout(UserService $userService)
    {
        $userService->setLoginUser(null);
        $this->redirect('user', 'login');
    }

    /**
     * @param UserModel $userModel
     * @param string $uid path(0,0)
     * @param string $username
     */
    public function ac_avatar_get(UserModel $userModel, string $uid, string $username = '')
    {
        $imgUrl = '/static/img/avatar.png';
        if ($username != null) {
            $imgUrl = $userModel->getAvatar($username);
        } else if ($uid != 0) {
            $imgUrl = $userModel->getAvatar($uid, true);
        }
        $this->redirect($imgUrl);
    }

    /**
     * @filter ajax
     * @filter api
     * @filter auth
     */
    public function ac_avatar_post(UserModel $userModel)
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $this->assign('tp_user', $tp_user);
        if (isset($_FILES['avatar'])) {
            $image_type = ['image/bmp', 'image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'application/x-bmp', 'application/x-jpg', 'application/x-png'];
            if (in_array($_FILES["avatar"]["type"], $image_type) && ($_FILES["avatar"]["size"] < 2000000)) {
                $t = time() % 1000;
                $url = BunnyPHP::getStorage()->upload("avatar/" . $tp_user['uid'] . '_' . $t . ".jpg", $_FILES["avatar"]["tmp_name"]);
                $userModel->updateAvatar($tp_user['uid'], $url);
                $response = ['ret' => 0, 'status' => 'ok', 'url' => $url];
            } else {
                $response = ['ret' => 2, 'status' => 'invalid file'];
            }
            $this->assignAll($response);
        }
        if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
            $this->redirect('setting', 'avatar');
        } else {
            $this->render('setting/avatar.php');
        }
    }

    /**
     * @filter auth info
     */
    public function ac_info()
    {
        if (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
            $username = $_REQUEST['username'] ?? '';
            $id_code = $_REQUEST['id_code'] ?? '';
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
                    $this->assignAll(['ret' => 1006, 'status' => 'invalid id code']);
                }
            } else {
                $row = (new UserModel())->getUserByUsername($username);
                if ($row['uid'] != null) {
                    $this->assignAll(['ret' => 0, 'status' => 'ok']);
                    $this->assignAll($row);
                } else {
                    $this->assignAll(['ret' => 1004, 'status' => 'invalid username']);
                }
            }
        }
        $this->render('user/info.php');
    }

    /**
     * @filter auth info
     */
    public function ac_code()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $code = (new IdCodeModel())->getIdCode($tp_user['uid']);
        $this->assignAll(['ret' => 0, 'status' => 'ok', 'code' => $code])->render();
    }

    /**
     * @param UserService $userService
     * @param string $username path(0)
     * @param string $tab path(1,post)
     */
    public function ac_detail(UserService $userService, string $username = '', string $tab = '')
    {
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
        $this->render('user/detail.php');
    }
}