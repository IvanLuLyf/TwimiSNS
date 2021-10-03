<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Config;
use BunnyPHP\Controller;

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/7/29
 * Time: 3:24
 */
class PostController extends Controller
{

    public function ac_index()
    {
        $this->redirect('post', 'list');
    }

    /**
     * @filter csrf
     * @filter auth
     */
    public function ac_create_get()
    {
        $this->render('post/create.html');
    }

    /**
     * @filter csrf check
     * @filter auth canFeed
     */
    public function ac_create_post()
    {
        if (isset($_POST['title']) && isset($_POST['content'])) {
            $tid = (new PostModel())->sendPost(BunnyPHP::app()->get('tp_user'), $_POST['title'], $_POST['content']);
            if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
                $this->redirect('post', 'view', ['tid' => $tid]);
            } elseif (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
                $this->assignAll(['ret' => 0, 'status' => 'ok', 'tid' => $tid])->render();
            }
        } else {
            $this->assignAll(['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => "必要参数为空"])->error();
        }
    }

    public function ac_view(array $path, UserService $userService)
    {
        $tid = isset($_REQUEST['tid']) ? $_REQUEST['tid'] : (isset($path[0]) ? $path[0] : 0);
        $post = (new PostModel())->getPostById($tid);
        $tp_user = $userService->getLoginUser();
        $showState = 0;
        if ($post != null) {
            if ($post['extra'] != '') {
                $extra = json_decode($post['extra'], true);
                if ($extra['type'] == 'paid' and $post['username'] != $tp_user['username'] and !(new PostPayModel())->check($tp_user['uid'], $tid)) {
                    $post['content'] = "[付费帖子]";
                    $this->assign('coin', $extra['price']);
                    $showState = 1;
                } elseif ($extra['type'] == 'login' and $tp_user == null) {
                    $post['content'] = "[登录可见]";
                    $showState = 2;
                }
            }
            $comments = (new CommentModel())->listComment($tid, 1);
            if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
                $this->assign('show_state', $showState);
                $this->assign('tp_user', $tp_user);
                $this->assign('cur_ctr', 'post');
                include APP_PATH . 'library/Parser.php';
                $parser = new HyperDown\Parser;
                $html_content = $parser->makeHtml($post['content']);
                $this->assign("html_content", $html_content);
                $oauth = [];
                if (Config::check("oauth")) {
                    $oauth = Config::load('oauth')->get('enabled', []);
                }
                $this->assign('oauth', $oauth);
            }
            $this->assign("post", $post)->assign('comments', $comments)
                ->render('post/view.html');
        } else {
            $this->assignAll(['ret' => 3001, 'status' => 'invalid tid', 'tp_error_msg' => '帖子不存在'])->error();
        }
    }

    function ac_list(array $path, UserService $userService)
    {
        $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : (isset($path[0]) ? $path[0] : 1);
        $cache = BunnyPHP::getCache();
        $tp_user = $userService->getLoginUser();
        if ($cache->has('post/list/' . $page)) {
            $cacheData = unserialize($cache->get('post/list/' . $page));
            $posts = $cacheData['posts'];
            $total = $cacheData['total'];
        } else {
            $postModel = (new PostModel());
            $posts = $postModel->getPostByPage($page);
            $total = $postModel->getTotal();
            $cache->set('post/list/' . $page, serialize(['posts' => $posts, 'total' => $total]));
        }
        foreach ($posts as &$post) {
            if ($post['extra'] != '') {
                $extra = json_decode($post['extra'], true);
                if ($extra['type'] == 'paid' and $post['username'] != $tp_user['username'] and !(new PostPayModel())->check($tp_user['uid'], $post['tid'])) {
                    $post['content'] = "[付费帖子]";
                    $post['coin'] = $extra['price'];
                } elseif ($extra['type'] == 'login' and $tp_user == null) {
                    $post['content'] = "[登录可见]";
                }
            }
        }
        $endPage = ceil($total / 20);
        if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
            include APP_PATH . 'library/Parser.php';
            $parser = new HyperDown\Parser;
            $this->assign('parser', $parser);
            $this->assign('tp_user', $tp_user)
                ->assign('cur_ctr', 'post')->assign('end_page', $endPage);
        }
        $this->assign('total', $total)->assign("page", $page)->assign("posts", $posts)
            ->render('post/list.html');
    }

    /**
     * @filter auth canFeed
     * @param int $tid path(0,0)
     */
    function ac_comment(int $tid = 0)
    {
        $post = (new PostModel())->getPostById($tid);
        if ($post != null) {
            (new CommentModel())->sendComment($tid, 1, BunnyPHP::app()->get('tp_user'), $_POST['content']);
            if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
                $this->redirect('post', 'view', ['tid' => $tid]);
            } elseif (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
                $this->assignAll(['ret' => 0, 'status' => 'ok'])->render();
            }
        } else {
            $this->assignAll(['ret' => 3001, 'status' => 'invalid tid', 'tp_error_msg' => '帖子不存在'])->error();
        }
    }

    function ac_search($word, UserService $userService, $page = 1, $limit = 20)
    {
        if ($word) {
            $result = (new PostModel())->search($word, $page, $limit);
            $endPage = ceil($result['total'] / $limit);
        } else {
            $result = ['total' => 0, 'posts' => []];
            $endPage = 0;
        }
        if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
            $this->assignAll(['tp_user' => $userService->getLoginUser(), 'cur_ctr' => 'post', 'end_page' => $endPage]);
        }
        $this->assignAll(['word' => $word, "page" => $page, 'total' => $result['total'], "posts" => $result['posts']])->render('post/search.html');
    }

    /**
     * @filter csrf
     * @filter auth
     * @param int $tid path(0,0)
     */
    function ac_buy_get(int $tid = 0)
    {
        $post = (new PostModel())->getPostById($tid);
        $tp_user = BunnyPHP::app()->get('tp_user');
        if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
            if ($post['extra'] != '') {
                $extra = json_decode($post['extra'], true);
                if ($extra['type'] == 'paid' and $post['username'] != $tp_user['username'] and !(new PostPayModel())->check($tp_user['uid'], $post['tid'])) {
                    $balance = doubleval((new CreditModel())->balance($tp_user['uid']));
                    if ($balance == -1) {
                        $this->redirect('pay', 'start');
                        return;
                    }
                    $this->assignAll(['coin' => $extra['price'], 'balance' => $balance, 'tid' => $tid, 'title' => $post['title']])->render('post/buy.html');
                }
            } else {
                $this->assignAll(['ret' => 5004, 'status' => 'no need to pay', 'tp_error_msg' => '帖子不需要支付'])->error();
            }
        }
    }

    /**
     * @filter csrf check
     * @filter auth canPay
     * @param int $tid path(0,0)
     */
    function ac_buy_post(int $tid = 0)
    {
        $post = (new PostModel())->getPostById($tid);
        $tp_user = BunnyPHP::app()->get('tp_user');
        if ($post['extra'] != '') {
            $extra = json_decode($post['extra'], true);
            if ($extra['type'] == 'paid' and $post['username'] != $tp_user['username'] and !(new PostPayModel())->check($tp_user['uid'], $post['tid'])) {
                $author = (new UserModel())->getUserByUsername($post['username']);
                if ((new CreditModel())->transfer($tp_user['uid'], $author['uid'], doubleval($extra['price']))) {
                    (new PostPayModel())->pay($tp_user['uid'], $post['tid']);
                    $this->assignAll(['ret' => 0, 'status' => 'ok', 'tid' => $tid])->render('post/buy.html');
                } else {
                    $this->assignAll(['ret' => 5003, 'status' => 'insufficient balance', 'tp_error_msg' => '余额不足'])->error();
                }
            }
        } else {
            $this->assignAll(['ret' => 5004, 'status' => 'no need to pay', 'tp_error_msg' => '帖子不需要支付'])->error();
        }
    }
}