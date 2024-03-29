<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Config;
use BunnyPHP\Controller;

/**
 * @author  IvanLu
 * @time  2018/7/29 3:24
 */
class PostController extends Controller
{
    private PostModel $postModel;

    public function __construct(PostModel $postModel)
    {
        $this->postModel = $postModel;
    }

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
        $this->render('post/create.php');
    }

    /**
     * @filter csrf check
     * @filter auth feed
     * @param string $title not_empty()
     * @param string $content not_empty()
     */
    public function ac_create_post(string $title, string $content)
    {
        $tid = $this->postModel->sendPost(BunnyPHP::app()->get('tp_user'), $title, $content);
        if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
            $this->redirect('post', 'view', ['tid' => $tid]);
        } elseif (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
            $this->assignAll(['ret' => 0, 'status' => 'ok', 'tid' => $tid])->render();
        }
    }

    /**
     * @param UserService $userService
     * @param int $tid path(0)
     */
    public function ac_view(UserService $userService, int $tid = 0)
    {
        $post = $this->postModel->getPostById($tid);
        $tp_user = $userService->getLoginUser();
        $showState = 0;
        if ($post != null) {
            if ($post['extra'] != '') {
                $extra = json_decode($post['extra'], true);
                if ($extra['type'] == 'paid'
                    && (
                        $tp_user == null
                        ||
                        ($post['username'] != $tp_user['username'] && !(new PostPayModel())->check($tp_user['uid'], $tid))
                    )) {
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
                $parser = new HyperDown\Parser;
                $html_content = $parser->makeHtml($post['content']);
                $this->assign("html_content", $html_content);
                $oauth = [];
                if (Config::check('oauth')) {
                    $oauth = Config::load('oauth')->get('enabled', []);
                }
                $this->assign('oauth', $oauth);
            }
            $this->assign('post', $post)
                ->assign('comments', $comments)
                ->render('post/view.php');
        } else {
            $this->assignAll(['ret' => 3001, 'status' => 'invalid tid', 'tp_error_msg' => '帖子不存在'])->error();
        }
    }

    /**
     * @param UserService $userService
     * @param int $page path(0)
     */
    function ac_list(UserService $userService, int $page = 1)
    {
        $tp_user = $userService->getLoginUser();
        $posts = $this->postModel->getPostByPage($page);
        $total = $this->postModel->getTotal();
        foreach ($posts as &$post) {
            if ($post['extra']) {
                $extra = json_decode($post['extra'], true);
                if ($extra['type'] == 'paid'
                    && (
                        $tp_user == null
                        ||
                        ($post['username'] != $tp_user['username'] && !(new PostPayModel())->check($tp_user['uid'], $post['tid']))
                    )) {
                    $post['content'] = "[付费帖子]";
                    $post['coin'] = $extra['price'];
                } elseif ($extra['type'] == 'login' and $tp_user == null) {
                    $post['content'] = "[登录可见]";
                }
            }
        }
        $endPage = ceil($total / 20);
        if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
            $parser = new HyperDown\Parser;
            $this->assign('parser', $parser);
            $this->assign('tp_user', $tp_user)
                ->assign('cur_ctr', 'post')->assign('end_page', $endPage);
        }
        $this->assign('total', $total)
            ->assign('page', $page)
            ->assign('posts', $posts)
            ->render('post/list.php');
    }

    /**
     * @filter auth feed
     * @param int $tid path(0)
     */
    function ac_comment(int $tid = 0)
    {
        $post = $this->postModel->getPostById($tid);
        if ($post != null) {
            (new CommentModel())->sendComment($tid, ConstUtil::MOD_FORUM, BunnyPHP::app()->get('tp_user'), $_POST['content']);
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
            $result = $this->postModel->search($word, $page, $limit);
            $endPage = ceil($result['total'] / $limit);
        } else {
            $result = ['total' => 0, 'posts' => []];
            $endPage = 0;
        }
        if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
            $this->assignAll(['tp_user' => $userService->getLoginUser(), 'cur_ctr' => 'post', 'end_page' => $endPage]);
        }
        $this->assignAll(['word' => $word, 'page' => $page, 'total' => $result['total'], 'posts' => $result['posts']])->render('post/search.php');
    }

    /**
     * @filter csrf
     * @filter auth
     * @param int $tid path(0)
     */
    function ac_buy_get(int $tid = 0)
    {
        $post = $this->postModel->getPostById($tid);
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
                    $this->assignAll(['coin' => $extra['price'], 'balance' => $balance, 'tid' => $tid, 'title' => $post['title']])->render('post/buy.php');
                }
            } else {
                $this->assignAll(['ret' => 5004, 'status' => 'no need to pay', 'tp_error_msg' => '帖子不需要支付'])->error();
            }
        }
    }

    /**
     * @filter csrf check
     * @filter auth pay
     * @param int $tid path(0)
     */
    function ac_buy_post(int $tid = 0)
    {
        $post = $this->postModel->getPostById($tid);
        $tp_user = BunnyPHP::app()->get('tp_user');
        if ($post['extra'] != '') {
            $extra = json_decode($post['extra'], true);
            if ($extra['type'] == 'paid' and $post['username'] != $tp_user['username'] and !(new PostPayModel())->check($tp_user['uid'], $post['tid'])) {
                $author = (new UserModel())->getUserByUsername($post['username']);
                if ((new CreditModel())->transfer($tp_user['uid'], $author['uid'], doubleval($extra['price']))) {
                    (new PostPayModel())->pay($tp_user['uid'], $post['tid']);
                    $this->assignAll(['ret' => 0, 'status' => 'ok', 'tid' => $tid])->render('post/buy.php');
                } else {
                    $this->assignAll(['ret' => 5003, 'status' => 'insufficient balance', 'tp_error_msg' => '余额不足'])->error();
                }
            }
        } else {
            $this->assignAll(['ret' => 5004, 'status' => 'no need to pay', 'tp_error_msg' => '帖子不需要支付'])->error();
        }
    }
}