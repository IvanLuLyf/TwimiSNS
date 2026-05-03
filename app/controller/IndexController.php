<?php

use BunnyPHP\Config;
use BunnyPHP\Controller;
use BunnyPHP\Request;

/**
 * @author IvanLu
 * @time 2026/05/03 15:30
 */
class IndexController extends Controller
{
    public function ac_index()
    {
        if (Config::check('config')) {
            $this->render('app.php');
        } else {
            $this->redirect('install', 'index');
        }
    }

    public function ac_boot(UserService $userService): array
    {
        $oauthKeys = [];
        if (Config::check('oauth')) {
            $oauthKeys = Config::load('oauth')->get('enabled', []);
        }
        $user = $userService->getLoginUser();

        $cfg = Config::load('config');

        $wallet = null;
        if ($user !== null) {
            $b = (new CreditModel())->balance($user['uid']);
            $wallet = [
                'active' => $b != -1,
                'credit' => $b != -1 ? (float)$b : null,
            ];
        }

        return [
            'ret' => 0,
            'status' => 'ok',
            'locale' => trim((string)$cfg->get('locale', 'zh-CN')),
            'site_name' => TP_SITE_NAME,
            'allow_reg' => (bool)$cfg->get('allow_reg'),
            'theme_color' => (string)$cfg->get('theme_color', '#1996ff'),
            'csrf_token' => Request::session('csrf_token'),
            'oauth' => $oauthKeys,
            'user' => $user ? UserController::slicePublic((new UserModel())->getUserByUid($user['uid'])) : null,
            'wallet' => $wallet,
            'copyright' => trim((string)$cfg->get('copyright', '')),
            'icp' => trim((string)$cfg->get('icp', '')),
        ];
    }

    /**
     * @filter csrf check
     */
    public function ac_out_post(UserService $userService): array
    {
        $userService->setLoginUser(null);
        return ['ret' => 0, 'status' => 'ok'];
    }
}