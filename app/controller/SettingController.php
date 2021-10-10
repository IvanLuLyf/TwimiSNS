<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Config;
use BunnyPHP\Controller;

/**
 * @author IvanLu
 * @time 2018/10/13 1:29
 * @filter auth
 */
class SettingController extends Controller
{
    private $user;

    public function __construct()
    {
        $this->user = BunnyPHP::app()->get('tp_user');
    }

    public function ac_index()
    {
        $this->redirect('setting', 'avatar');
    }

    public function ac_avatar()
    {
        $this->assign('cur_st', 'avatar')->render('setting/avatar.php');
    }

    public function ac_gravatar(AvatarModel $avatarModel)
    {
        if (!empty($this->user['uid'])) {
            $avatarModel->upload($this->user['uid'], 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->user['email']))));
        }
        $this->redirect('setting', 'avatar');
    }

    /**
     * @param string $type path(0)
     */
    public function ac_oauth(string $type = '')
    {
        if (Config::check('oauth')) {
            $oauth_enabled = Config::load('oauth')->get('enabled', []);
            $type = $type == '' ? $oauth_enabled[0][0] : $type;
            $bind = (new BindModel())->where(['uid=:u and type=:t'], ['u' => $this->user['uid'], 't' => $type])->fetch();
            if ($bind != null) {
                $this->assign('tp_bind', $bind);
                $image = (new OauthService())->avatar($type, $bind['bind'], $bind['token']);
                $this->assign('avatar', $image);
            }
            $name = '';
            foreach ($oauth_enabled as $o) {
                if ($o[0] == $type) {
                    $name = $o[1];
                    break;
                }
            }
            $this->assign("oauth_list", $oauth_enabled);
            $this->assign('cur_st', "oauth")
                ->assign('oauth', ['type' => $type, 'name' => $name])
                ->render('setting/oauth.php');
        } else {
            $this->assignAll(['ret' => 1007, 'status' => 'oauth is not enabled', 'tp_error_msg' => '站点未开启OAuth'])->error();
        }
    }

    /**
     * @param string $type path(0)
     */
    public function ac_oauth_avatar(AvatarModel $avatarModel, string $type = '')
    {
        if (!empty($this->user['uid'])) {
            $avatarModel->upload($this->user['uid'], $_REQUEST['avatar']);
        }
        $this->redirect('setting', 'oauth', ['type' => $type]);
    }
}