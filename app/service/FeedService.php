<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Service;

/**
 * @author IvanLu
 * @time 2021/10/12 23:04
 */
class FeedService extends Service
{
    /**
     * @var mixed|null
     */
    private $user;
    private FriendModel $friendModel;
    private FeedModel $feedModel;
    private FeedImageModel $feedImageModel;

    public function __construct(FriendModel $friendModel, FeedModel $feedModel, FeedImageModel $feedImageModel)
    {
        $this->user = BunnyPHP::app()->get('tp_user');
        $this->friendModel = $friendModel;
        $this->feedModel = $feedModel;
        $this->feedImageModel = $feedImageModel;
    }

    public function timeline($page = 1): array
    {
        $friends = $this->friendModel->where(['uid = ? and state = ?'], [$this->user['uid'], FriendModel::STATE_FRIEND])->fetchAll(['friend', 'remark']);
        $friendIds = array_column($friends, 'friend');
        $friendNotes = array_column($friends, 'remark', 'friend');
        $friendIds[] = $this->user['uid'];
        $feeds = $this->feedModel->where('uid in (' . implode(',', $friendIds) . ')')
            ->order(['tid desc'])
            ->limit(20, ($page - 1) * 20)
            ->fetchAll();
        $tidArr = array_column($feeds, 'tid');
        $imageMap = $this->loadFeedImages($tidArr);
        foreach ($feeds as &$feed) {
            $feed['remark'] = $friendNotes[$feed['uid']] ?? $feed['nickname'];
            if (isset($imageMap[$feed['tid']])) {
                $feed['images'] = $imageMap[$feed['tid']];
            }
        }
        return $feeds;
    }

    public function userTimeline($username, $page = 1): array
    {
        $feeds = $this->feedModel->where('username=:u', ['u' => $username])
            ->order(['tid desc'])
            ->limit(20, ($page - 1) * 20)
            ->fetchAll();
        $tidArr = array_column($feeds, 'tid');
        $imageMap = $this->loadFeedImages($tidArr);
        foreach ($feeds as &$feed) {
            if (isset($imageMap[$feed['tid']])) {
                $feed['images'] = $imageMap[$feed['tid']];
            }
        }
        return $feeds;
    }

    public function loadFeedImages(array $feedIds): array
    {
        $images = $this->feedImageModel->where('tid in (' . implode(',', $feedIds) . ')')->fetchAll(['tid', 'url']);
        $imageMap = [];
        foreach ($images as $image) {
            $imageMap[$image['tid']][] = ['url' => $image['url']];
        }
        return $imageMap;
    }
}