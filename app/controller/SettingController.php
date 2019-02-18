<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/10/13
 * Time: 1:29
 */

class SettingController extends Controller
{
    public function ac_index()
    {
        $this->redirect('setting', 'avatar');
    }

    /**
     * @filter auth
     */
    public function ac_avatar()
    {
        $this->assign('tp_user', BunnyPHP::app()->get('tp_user'))->assign('cur_st', 'avatar');
        $this->render('setting/avatar.html');
    }

    /**
     * @filter auth
     */
    public function ac_gravatar()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        if (!empty($tp_user['uid'])) {
            (new AvatarModel())->upload($tp_user['uid'], 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($tp_user['email']))));
        }
        $this->assign('tp_user', $tp_user);
        $this->redirect('setting', 'avatar');
    }

    /**
     * @filter auth
     */
    public function ac_qq()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $model = new QqBindModel();
        $bind = $model->where(['uid = :u'], ['u' => $tp_user['uid']])->fetch();
        if ($bind != null) {
            $this->assign('tp_bind', $bind);
            $qq_key = Config::load('oauth')->get('qq')['key'];
            $this->assign('avatar', $model->getAvatar($qq_key, $tp_user['uid']));
        }
        $this->assign('cur_st', 'qq')
            ->assign('oauth', ['type' => 'qq', 'name' => 'QQ'])
            ->assign('tp_user', $tp_user)
            ->render('setting/oauth.html');
    }

    /**
     * @filter auth
     */
    public function ac_qq_avatar()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        if (!empty($tp_user['uid'])) {
            (new AvatarModel())->upload($tp_user['uid'], $_REQUEST['avatar']);
        }
        $this->assign('tp_user', $tp_user);
        $this->redirect('setting', 'qq');
    }

    /**
     * @filter auth
     */
    public function ac_wb()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $bind = (new SinaBindModel())->where(['uid = :u'], ['u' => $tp_user['uid']])->fetch();
        if ($bind != null) {
            include_once(APP_PATH . 'library/SinaWeibo/saetv2.ex.class.php');
            $this->assign('tp_bind', $bind);
            $o = Config::load('oauth')->get('wb');
            $c = new SaeTClientV2($o['key'], $o['secret'], $bind['token']);
            $user_message = $c->show_user_by_id($bind['buid']);
            $image = str_replace('http:', 'https:', $user_message['avatar_large']);
            $this->assign('avatar', $image);
        }
        $this->assign('cur_st', 'wb')
            ->assign('oauth', ['type' => 'wb', 'name' => '微博'])
            ->assign('tp_user', $tp_user)
            ->render('setting/oauth.html');
    }

    /**
     * @filter auth
     */
    public function ac_wb_avatar()
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        if (!empty($tp_user['uid'])) {
            (new AvatarModel())->upload($tp_user['uid'], $_REQUEST['avatar']);
        }
        $this->assign('tp_user', $tp_user);
        $this->redirect('setting', 'wb');
    }
}