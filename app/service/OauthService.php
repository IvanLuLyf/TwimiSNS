<?php

use BunnyPHP\Config;
use BunnyPHP\Service;

/**
 * @author IvanLu
 * @time 2019/2/26 17:31
 */
class OauthService extends Service
{
    public function oauth($type): array
    {
        switch ($type) {
            case 'qq':
                $oauth = Config::load('oauth')->get('qq');
                return $this->qq_oauth($oauth, $_GET['code']);
            case 'wb':
                $oauth = Config::load('oauth')->get('wb');
                return $this->wb_oauth($oauth, $_GET['code']);
            case 'gh':
                $oauth = Config::load('oauth')->get('gh');
                return $this->gh_oauth($oauth, $_GET['code']);
            default:
                $oauth = Config::load('oauth')->get($type);
                return $this->tm_oauth($oauth, $_GET['code']);
        }
    }

    public function avatar($type, $bind_id, $token = ''): string
    {
        switch ($type) {
            case 'qq':
                $oauth = Config::load('oauth')->get('qq');
                return $this->qq_avatar($oauth, $bind_id);
            case 'wb':
                return $this->wb_avatar($bind_id, $token);
            case 'gh':
                return "https://avatars.githubusercontent.com/u/$bind_id";
            default:
                $oauth = Config::load('oauth')->get($type);
                return "{$oauth['url']}/user/avatar/$bind_id";
        }
    }

    private function tm_oauth($oauth, $code): array
    {
        $strInfo = RequestUtil::doPost("{$oauth['url']}/api/oauth/token", "client_id={$oauth['key']}&client_secret={$oauth['secret']}&code=$code");
        $oauth_data = json_decode($strInfo, true);
        $oauthToken = $oauth_data['token'];
        $strUserInfo = RequestUtil::doPost("{$oauth['url']}/api/user/info", "client_id={$oauth['key']}&token=$oauthToken");
        $user_info = json_decode($strUserInfo, true);
        return ['uid' => $user_info['uid'], 'nickname' => $user_info['nickname'], 'token' => $oauthToken, 'expire' => $oauth_data['expire']];
    }

    private function qq_oauth($oauth, $code): array
    {
        $token_url = 'https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&' . 'client_id=' . $oauth['key'] . '&redirect_uri=' . urlencode($oauth['callback']) . '&client_secret=' . $oauth['secret'] . '&code=' . $code;
        $token = [];
        parse_str(RequestUtil::doGet($token_url), $token);
        $open_id_str = RequestUtil::doGet('https://graph.qq.com/oauth2.0/me?access_token=' . $token['access_token']);
        if (strpos($open_id_str, "callback") !== false) {
            $l_pos = strpos($open_id_str, "(");
            $r_pos = strrpos($open_id_str, ")");
            $open_id_str = substr($open_id_str, $l_pos + 1, $r_pos - $l_pos - 1);
        }
        $open_id = json_decode($open_id_str, true);
        $user_info_url = 'https://graph.qq.com/user/get_user_info?' . 'access_token=' . $token['access_token'] . '&oauth_consumer_key=' . $oauth['key'] . '&openid=' . $open_id['openid'] . '&format=json';
        $user_info = json_decode(RequestUtil::doGet($user_info_url), true);
        return ['uid' => $open_id['openid'], 'nickname' => $user_info['nickname'], 'token' => $token['access_token'], 'expire' => time() + $token['expires_in']];
    }

    private function gh_oauth($oauth, $code): array
    {
        $token_url = 'https://github.com/login/oauth/access_token';
        $token = [];
        parse_str(RequestUtil::doPost($token_url, "client_id=" . $oauth['key'] . "&client_secret=" . $oauth['secret'] . "&code=" . $code . "&redirect_uri=" . $oauth['callback']), $token);
        $user_info_url = "https://api.github.com/user?access_token=" . $token['access_token'];
        $user_info = json_decode(RequestUtil::doGet($user_info_url), true);
        return ['uid' => $user_info['id'], 'nickname' => $user_info['login'], 'token' => $token['access_token'], 'expire' => time()];
    }

    private function wb_oauth($oauth, $code): array
    {
        $token_url = 'https://api.weibo.com/oauth2/access_token';
        $token = json_decode(RequestUtil::doPost($token_url, "client_id=" . $oauth['key'] . "&client_secret=" . $oauth['secret'] . "&grant_type=authorization_code&code=" . $code . "&redirect_uri=" . $oauth['callback']), TRUE);
        $user_info_url = "https://api.weibo.com/2/users/show.json?access_token=" . $token['access_token'] . "&uid=" . $token['uid'];
        $user_info = json_decode(RequestUtil::doGet($user_info_url), true);
        return ['uid' => $token['uid'], 'nickname' => $user_info['screen_name'], 'token' => $token['access_token'], 'expire' => time() + $token['expires_in']];
    }

    private function wb_avatar($bind_id, $token): string
    {
        if ($token == '') {
            $token = (new BindModel())->where(['uid = 1 and type="wb"'], [])->fetch()['token'];
        }
        $user_info_url = "https://api.weibo.com/2/users/show.json?access_token=$token&uid=$bind_id";
        $user_info = json_decode(RequestUtil::doGet($user_info_url), true);
        return str_replace('http:', 'https:', $user_info['avatar_large']);
    }

    private function qq_avatar($oauth, $bind_id): string
    {
        return "https://qzapp.qlogo.cn/qzapp/{$oauth['key']}/$bind_id/100";
    }
}