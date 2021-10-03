<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Controller;

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/1/1
 * Time: 22:57
 */
class FeedController extends Controller
{
    /**
     * @filter auth canFeed
     */
    public function ac_send()
    {
        if (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
            if (isset($_POST['content'])) {
                $imageCount = 0;
                if (isset($_FILES['images'])) {
                    $paths = $_FILES['images']["tmp_name"];
                    if (is_array($paths)) {
                        $imageCount = count($paths);
                    } else {
                        $imageCount = 1;
                    }
                }
                $tp_user = BunnyPHP::app()->get('tp_user');
                $tp_api = BunnyPHP::app()->get('tp_api');
                $feedId = (new FeedModel())->sendFeed($tp_user, $_POST['content'], $tp_api['name'], $imageCount);
                $feedImageModel = new FeedImageModel();
                if ($imageCount > 0) {
                    $image_type = ['image/bmp', 'image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'application/x-bmp', 'application/x-jpg', 'application/x-png'];
                    for ($i = 0; $i < $imageCount; $i++) {
                        if (in_array($_FILES["images"]["type"][$i], $image_type) && ($_FILES["images"]["size"][$i] < 2000000)) {
                            $t = time() % 1000;
                            $filename = "feed/$feedId-$i-$t.jpg";
                            $url = BunnyPHP::getStorage()->upload($filename, $_FILES["images"]["tmp_name"][$i]);
                            $feedImageModel->upload($tp_user['uid'], $feedId, $url);
                        }
                    }
                }
                $this->assignAll(['ret' => 0, 'status' => 'ok', 'tid' => $feedId]);
            } else {
                $this->assignAll(['ret' => -7, 'status' => 'parameter cannot be empty']);
            }
        }
        $this->render();
    }

    /**
     * @filter auth canFeed
     * @param int $tid path(0)
     * @param int $page path(1)
     */
    public function ac_view(int $tid = 0, int $page = 1)
    {
        if (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
            $tp_user = BunnyPHP::app()->get('tp_user');
            $this->assignAll(['ret' => 0, 'status' => 'ok', 'page' => $page]);
            if ($tid == 0) {
                $feeds = (new FeedModel())->listFeed($tp_user['uid'], $page);
                foreach ($feeds as &$feed) {
                    if ($feed['image'] != null && $feed['image'] > 0) {
                        $feed['images'] = (new FeedImageModel())->getFeedImageByTid($feed['tid']);
                    }
                }
                $note_info = (new NotificationModel())->getUnreadCnt($tp_user['uid']);
                $user_info = (new UserInfoModel())->get($tp_user['uid']);
                $this->assignAll(['tid' => $tid, 'note_count' => $note_info[0], 'note_uid' => $note_info[1], 'user_info' => $user_info, 'feeds' => $feeds]);
            } else {
                $feed = (new FeedModel())->getFeed($tid);
                $noteName = (new FriendModel())->getNoteNameByUsername($tp_user['uid'], $feed['username']);
                $feed['notename'] = $noteName;
                if ($feed['image'] != null && $feed['image'] > 0) {
                    $feed['images'] = (new FeedImageModel())->getFeedImageByTid($feed['tid']);
                }
                $comments = (new CommentModel())->listComment($tid, 3, $page, $tp_user['uid']);
                $this->assignAll(['feed' => $feed, 'comments' => $comments]);
            }
        }
        $this->render();
    }

    /**
     * @filter auth canFeed
     * @param string $username path(0)
     * @param int $page path(1)
     */
    public function ac_list(string $username = '', int $page = 1)
    {
        $user = (new UserModel())->getUserByUsername($username);
        if (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
            if ($user['uid'] != null) {
                $feeds = (new FeedModel())->userFeed($username, $page);
                foreach ($feeds as &$feed) {
                    if ($feed['image'] != null && $feed['image'] > 0) {
                        $feed['images'] = (new FeedImageModel())->getFeedImageByTid($feed['tid']);
                    }
                }
                $user_info = (new UserInfoModel())->get($user['uid']);
                $this->assignAll(['ret' => 0, 'status' => 'ok', 'page' => $page, 'user_info' => $user_info, 'feeds' => $feeds]);
            } else {
                $this->assignAll(['ret' => 1002, 'status' => "user does not exist"]);
            }
        }
        $this->render();
    }

    /**
     * @filter auth canFeed
     * @param int $tid path(0)
     */
    public function ac_comment(int $tid = 0)
    {
        if (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
            if (isset($_POST['content'])) {
                if ($feed = (new FeedModel())->getFeed($tid)) {
                    $tp_user = BunnyPHP::app()->get('tp_user');
                    $comment_id = (new CommentModel())->sendComment($tid, 3, $tp_user, $_POST['content']);
                    (new NotificationModel())->notify(3, $tid, $feed['uid'], $tp_user['uid'], 'comment', $_POST['content']);
                    $this->assignAll(['ret' => 0, 'status' => 'ok', 'tid' => $tid, 'cid' => $comment_id]);
                } else {
                    $this->assignAll(['ret' => 3001, 'status' => 'invalid tid']);
                }
            } else {
                $this->assignAll(['ret' => -7, 'status' => 'parameter cannot be empty']);
            }
        }
        $this->render();
    }

    /**
     * @filter auth canFeed
     */
    function ac_like()
    {
        $tid = $_REQUEST['tid'] ?? 0;
        if (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
            $feedModel = new FeedModel();
            if ($feed = $feedModel->getFeed($tid)) {
                $tp_user = BunnyPHP::app()->get('tp_user');
                if ((new LikeModel())->isLike($tp_user['uid'], 3, $tid) == 1) {
                    $this->assignAll(['ret' => 3003, 'status' => 'already liked']);
                } else {
                    (new LikeModel())->like($tp_user['uid'], 3, $tid);
                    $like_num = $feedModel->likeFeed($tid);
                    (new NotificationModel())->notify(3, $tid, $feed['uid'], $tp_user['uid'], 'like', '');
                    $this->assignAll(['ret' => 0, 'status' => 'ok', 'like_num' => $like_num]);
                }
            } else {
                $this->assignAll(['ret' => 3001, 'status' => 'invalid tid']);
            }
        }
        $this->render();
    }

    /**
     * @filter auth canFeed
     */
    function ac_delete()
    {
        $tid = $_REQUEST['tid'] ?? 0;
        if (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
            $feedModel = new FeedModel();
            if ($feed = $feedModel->getFeed($tid)) {
                $tp_user = BunnyPHP::app()->get('tp_user');
                if ($feed['username'] == $tp_user['username']) {
                    $images = (new FeedImageModel())->getFeedImageByTid($tid);
                    if ($images != null) {
                        foreach ($images as $image) {
                            BunnyPHP::getStorage()->remove($image['url']);
                        }
                    }
                    (new FeedImageModel())->where('tid=:t', ['t' => $tid])->delete();
                    (new CommentModel())->where('aid=3 and tid=:t', ['t' => $tid])->delete();
                    (new LikeModel())->where('aid=3 and tid=:t', ['t' => $tid])->delete();
                    $feedModel->where('tid=:t', ['t' => $tid])->delete();
                    $this->assignAll(['ret' => 0, 'status' => 'ok']);
                } else {
                    $this->assignAll(['ret' => 3002, 'status' => 'permission denied']);
                }
            } else {
                $this->assignAll(['ret' => 3001, 'status' => 'invalid tid']);
            }
        }
        $this->render();
    }
}