<?php

use BunnyPHP\Controller;

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/5/1
 * Time: 19:33
 */
class ChannelController extends Controller
{
    /**
     * @param string $name path(0)
     */
    public function ac_view(string $name)
    {
        $channel = (new ChannelModel())->getChannelByName($name);
        $articles = (new ArticleModel())->getArticlesByChannel($channel['cid']);
        $this->assignAll(['ret' => 0, 'status' => 'ok', 'channel' => $channel, 'articles' => $articles])->render('channel/channel.html');
    }
}