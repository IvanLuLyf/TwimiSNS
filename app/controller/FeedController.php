<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Controller;

/**
 * @author IvanLu
 * @time 2018/1/1 22:57
 * @filter auth feed
 */
class FeedController extends Controller
{
    private FeedModel $feedModel;
    /**
     * @var mixed|null
     */
    private $user;

    public function __construct(FeedModel $feedModel)
    {
        $this->user = BunnyPHP::app()->get('tp_user');
        $this->feedModel = $feedModel;
    }

    /**
     * @param string $content not_empty()
     * @return array
     */
    public function ac_send(string $content): array
    {
        $imageCount = 0;
        if (isset($_FILES['images'])) {
            $paths = $_FILES['images']["tmp_name"];
            if (is_array($paths)) {
                $imageCount = count($paths);
            } else {
                $imageCount = 1;
            }
        }
        $tp_api = BunnyPHP::app()->get('tp_api');
        $feedId = $this->feedModel->sendFeed($this->user, $content, $tp_api['name'], $imageCount);
        $feedImageModel = new FeedImageModel();
        if ($imageCount > 0) {
            $image_type = ['image/bmp', 'image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'application/x-bmp', 'application/x-jpg', 'application/x-png'];
            for ($i = 0; $i < $imageCount; $i++) {
                if (in_array($_FILES["images"]["type"][$i], $image_type) && ($_FILES["images"]["size"][$i] < 2000000)) {
                    $t = time() % 1000;
                    $filename = "feed/$feedId-$i-$t.jpg";
                    $url = BunnyPHP::getStorage()->upload($filename, $_FILES["images"]["tmp_name"][$i]);
                    $feedImageModel->upload($this->user['uid'], $feedId, $url);
                }
            }
        }
        return ['ret' => 0, 'status' => 'ok', 'tid' => $feedId];
    }

    /**
     * @param int $tid path(0)
     * @param int $page path(1)
     */
    public function ac_view(int $tid = 0, int $page = 1): array
    {
        $res = ['ret' => 0, 'status' => 'ok', 'page' => $page];
        if ($tid == 0) {
            $feeds = $this->feedModel->listFeed($this->user['uid'], $page);
            foreach ($feeds as &$feed) {
                if ($feed['image'] != null && $feed['image'] > 0) {
                    $feed['images'] = (new FeedImageModel())->getFeedImageByTid($feed['tid']);
                }
            }
            $note_info = (new NotificationModel())->getUnreadCnt($this->user['uid']);
            $user_info = (new UserInfoModel())->get($this->user['uid']);
            return array_merge($res, ['tid' => $tid, 'note_count' => $note_info[0], 'note_uid' => $note_info[1], 'user_info' => $user_info, 'feeds' => $feeds]);
        } else {
            $feed = $this->feedModel->getFeed($tid);
            $noteName = (new FriendModel())->getNoteNameByUsername($this->user['uid'], $feed['username']);
            $feed['notename'] = $noteName;
            if ($feed['image'] != null && $feed['image'] > 0) {
                $feed['images'] = (new FeedImageModel())->getFeedImageByTid($feed['tid']);
            }
            $comments = (new CommentModel())->listComment($tid, ConstUtil::MOD_FEED, $page, $this->user['uid']);
            return array_merge($res, ['feed' => $feed, 'comments' => $comments]);
        }
    }

    /**
     * @param string $username path(0)
     * @param int $page path(1)
     */
    public function ac_list(string $username = '', int $page = 1): array
    {
        $user = (new UserModel())->getUserByUsername($username);
        if (!$user['uid']) {
            return ['ret' => 1002, 'status' => "user does not exist"];
        }
        $feeds = $this->feedModel->userFeed($username, $page);
        foreach ($feeds as &$feed) {
            if ($feed['image'] != null && $feed['image'] > 0) {
                $feed['images'] = (new FeedImageModel())->getFeedImageByTid($feed['tid']);
            }
        }
        $user_info = (new UserInfoModel())->get($user['uid']);
        return ['ret' => 0, 'status' => 'ok', 'page' => $page, 'user_info' => $user_info, 'feeds' => $feeds];
    }

    /**
     * @param int $tid path(0)
     * @param string $content not_empty()
     */
    public function ac_comment(string $content, int $tid = 0): array
    {
        if ($feed = $this->feedModel->getFeed($tid)) {
            $comment_id = (new CommentModel())->sendComment($tid, ConstUtil::MOD_FEED, $this->user, $content);
            (new NotificationModel())->notify(3, $tid, $feed['uid'], $this->user['uid'], 'comment', $content);
            return ['ret' => 0, 'status' => 'ok', 'tid' => $tid, 'cid' => $comment_id];
        } else {
            return ['ret' => 3001, 'status' => 'invalid tid'];
        }
    }

    public function ac_like(LikeModel $likeModel, int $tid = 0): array
    {
        if ($feed = $this->feedModel->getFeed($tid)) {
            if ($likeModel->isLike($this->user['uid'], ConstUtil::MOD_FEED, $tid) == 1) {
                return ['ret' => 3003, 'status' => 'already liked'];
            } else {
                $likeModel->like($this->user['uid'], ConstUtil::MOD_FEED, $tid);
                $like_num = $this->feedModel->likeFeed($tid);
                (new NotificationModel())->notify(ConstUtil::MOD_FEED, $tid, $feed['uid'], $this->user['uid'], 'like', '');
                return ['ret' => 0, 'status' => 'ok', 'like_num' => $like_num];
            }
        } else {
            return ['ret' => 3001, 'status' => 'invalid tid'];
        }
    }

    public function ac_delete(int $tid = 0): array
    {
        if ($feed = $this->feedModel->getFeed($tid)) {
            if ($feed['username'] == $this->user['username']) {
                $images = (new FeedImageModel())->getFeedImageByTid($tid);
                if ($images != null) {
                    foreach ($images as $image) {
                        BunnyPHP::getStorage()->remove($image['url']);
                    }
                }
                (new FeedImageModel())->where('tid=:t', ['t' => $tid])->delete();
                (new CommentModel())->where('aid=3 and tid=:t', ['t' => $tid])->delete();
                (new LikeModel())->where('aid=3 and tid=:t', ['t' => $tid])->delete();
                $this->feedModel->where('tid=:t', ['t' => $tid])->delete();
                return ['ret' => 0, 'status' => 'ok'];
            } else {
                return ['ret' => 3002, 'status' => 'permission denied'];
            }
        } else {
            return ['ret' => 3001, 'status' => 'invalid tid'];
        }
    }
}