<?php

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
        if ($this->_mode == BunnyPHP::MODE_API) {
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
                            $url = $this->storage()->upload($filename, $_FILES["images"]["tmp_name"][$i]);
                            $feedImageModel->upload($tp_user['uid'], $feedId, $url);
                        }
                    }
                }
                $this->assign('ret', 0);
                $this->assign('status', 'ok');
                $this->assign('tid', $feedId);
            } else {
                $this->assign('ret', 1004);
                $this->assign('status', 'empty arguments');
            }
        }
        $this->render();
    }

    /**
     * @filter auth canFeed
     * @param array $path
     */
    public function ac_view(array $path)
    {
        $tid = isset($_REQUEST['tid']) ? $_REQUEST['tid'] : (isset($path[0]) ? $path[0] : 0);
        $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : (isset($path[1]) ? $path[1] : 1);
        if ($this->_mode == BunnyPHP::MODE_API) {
            $tp_user = BunnyPHP::app()->get('tp_user');
            $this->assign('ret', 0)->assign('status', 'ok')->assign('page', $page);
            if ($tid == 0) {
                $feeds = (new FeedModel())->listFeed($tp_user['uid'], $page);
                foreach ($feeds as &$feed) {
                    if ($feed['image'] != null && $feed['image'] > 0) {
                        $feed['images'] = (new FeedImageModel())->getFeedImageByTid($feed['tid']);
                    }
                }
                $this->assign('tid', $tid);
                $this->assign('user', $tp_user);
                $this->assign('noticnt', (new NotificationModel())->getUnreadCnt($tp_user['uid'])['noticnt']);
                $this->assign('feeds', $feeds);
            } else {
                $feed = (new FeedModel())->getFeed($tid);
                if ($feed['image'] != null && $feed['image'] > 0) {
                    $feed['images'] = (new FeedImageModel())->getFeedImageByTid($feed['tid']);
                }
                $comments = (new CommentModel())->listComment(3, $tid, $page);
                $this->assign('feed', $feed);
                $this->assign('comments', $comments);
            }
        }
        $this->render();
    }

    /**
     * @filter auth canFeed
     * @param array $path
     */
    public function ac_comment(array $path)
    {
        $tid = isset($_REQUEST['tid']) ? $_REQUEST['tid'] : (isset($path[0]) ? $path[0] : 0);
        if ($this->_mode == BunnyPHP::MODE_API) {
            if (isset($_POST['content'])) {
                if ($feed = (new FeedModel())->getFeed($tid)) {
                    $tp_user = BunnyPHP::app()->get('tp_user');
                    $comment_id = (new CommentModel())->sendComment($tid, 3, $tp_user, $_POST['content']);
                    (new NotificationModel())->notify(3, $tid, $feed['uid'], $tp_user['uid'], 'comment', $_POST['content']);
                    $this->assign('ret', 0);
                    $this->assign('status', 'ok');
                    $this->assign('tid', $tid);
                    $this->assign('cid', $comment_id);
                } else {
                    $this->assign('ret', 3001);
                    $this->assign('status', 'invalid tid');
                }
            } else {
                $this->assign('ret', 1004);
                $this->assign('status', 'empty arguments');
            }
        }
        $this->render();
    }

    /**
     * @filter auth canFeed
     */
    function ac_like()
    {
        $tid = isset($_REQUEST['tid']) ? $_REQUEST['tid'] : 0;
        if ($this->_mode == BunnyPHP::MODE_API) {
            $feedModel = new FeedModel();
            if ($feed = $feedModel->getFeed($tid)) {
                $tp_user = BunnyPHP::app()->get('tp_user');
                if ((new LikeModel())->isLike($tp_user['uid'], 3, $tid) == 1) {
                    $this->assign('ret', 3002);
                    $this->assign('status', 'already liked');
                } else {
                    (new LikeModel())->like($tp_user['uid'], 3, $tid);
                    $like_num = $feedModel->likeFeed($tid);
                    (new NotificationModel())->notify(3, $tid, $feed['uid'], $tp_user['uid'], 'like', '');
                    $this->assign('ret', 0);
                    $this->assign('status', 'ok');
                    $this->assign('like_num', $like_num);
                }
            } else {
                $this->assign('ret', 3001);
                $this->assign('status', 'invalid tid');
            }
        }
        $this->render();
    }
}