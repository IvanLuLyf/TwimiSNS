<?php

use BunnyPHP\Controller;

/**
 * @author IvanLu
 * @time 2026/5/3 16:17
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
        $this->assignAll(['ret' => 0, 'status' => 'ok', 'channel' => $channel, 'articles' => $articles])->render('app.php');
    }
}