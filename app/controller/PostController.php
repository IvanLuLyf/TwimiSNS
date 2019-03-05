<?php
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
        $this->assign('csrf_token', BunnyPHP::app()->get('csrf_token'));
        $this->assign('tp_user', BunnyPHP::app()->get('tp_user'));
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
            if ($this->_mode == BunnyPHP::MODE_NORMAL) {
                $this->redirect('post', 'view', ['tid' => $tid]);
            } elseif ($this->_mode == BunnyPHP::MODE_API) {
                $this->assign('ret', 0)->assign('status', 'ok')->assign('tid', $tid)->render();
            }
        } else {
            $this->assign('ret', 1004)->assign('status', 'empty arguments')->assign('tp_error_msg', "必要参数为空")
                ->render('common/error.html');
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
            if ($this->_mode == BunnyPHP::MODE_NORMAL) {
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
            $this->assign('ret', 3001)->assign('status', 'invalid tid')->assign('tp_error_msg', "帖子不存在")
                ->render('common/error.html');
        }
    }

    function ac_list(array $path, UserService $userService)
    {
        $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : isset($path[0]) ? $path[0] : 1;
        $postModel = (new PostModel());
        $posts = $postModel->getPostByPage($page);
        $tp_user = $userService->getLoginUser();
        foreach ($posts as &$post) {
            if ($post['extra'] != '') {
                $extra = json_decode($post['extra'], true);
                if ($extra['type'] == 'paid' and $post['username'] != $tp_user['username'] and !(new PostPayModel())->check($tp_user['uid'], $post['tid'])) {
                    $post['content'] = "[付费帖子]";
                    $this->assign('coin', $extra['price']);
                } elseif ($extra['type'] == 'login' and $tp_user == null) {
                    $post['content'] = "[登录可见]";
                }
            }
        }
        $total = $postModel->getTotal();
        $endPage = ceil($total / 20);
        if ($this->_mode == BunnyPHP::MODE_NORMAL) {
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
     * @param array $path
     * @filter auth canFeed
     */
    function ac_comment(array $path)
    {
        $tid = isset($_REQUEST['tid']) ? $_REQUEST['tid'] : (isset($path[0]) ? $path[0] : 0);
        $post = (new PostModel())->getPostById($tid);
        if ($post != null) {
            (new CommentModel())->sendComment($tid, 1, BunnyPHP::app()->get('tp_user'), $_POST['content']);
            if ($this->_mode == BunnyPHP::MODE_NORMAL) {
                $this->redirect('post', 'view', ['tid' => $tid]);
            } elseif ($this->_mode == BunnyPHP::MODE_API) {
                $this->assign('ret', 0)->assign('status', 'ok')->render();
            }
        } else {
            $this->assign('ret', 3001);
            $this->assign('status', 'invalid tid');
            $this->assign('tp_error_msg', "帖子不存在");
            $this->render('common/error.html');
        }
    }

    function ac_search(array $path, UserService $userService)
    {
        if (isset($_REQUEST['word']) && $_REQUEST['word'] != '') {
            $word = $_REQUEST['word'];
            $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
            $result = (new PostModel())->search($word, $page);
            $endPage = ceil($result['total'] / 20);
            if ($this->_mode == BunnyPHP::MODE_NORMAL) {
                $this->assign('tp_user', $userService->getLoginUser())
                    ->assign('cur_ctr', 'post')->assign('end_page', $endPage);
            }
            $this->assign('word', $word);
            $this->assign("page", $page)->assign('total', $result['total'])->assign("posts", $result['posts'])
                ->render('post/search.html');
        } else {
            if ($this->_mode == BunnyPHP::MODE_NORMAL) {
                $this->assign('word', '');
                $this->assign('total', 0)->assign("posts", []);
                $this->assign('tp_user', $userService->getLoginUser())->render('post/search.html');
            }
        }
    }

    /**
     * @filter csrf
     * @filter auth
     * @param array $path
     */
    function ac_buy_get(array $path)
    {
        $tid = isset($_REQUEST['tid']) ? $_REQUEST['tid'] : (isset($path[0]) ? $path[0] : 0);
        $post = (new PostModel())->getPostById($tid);
        $tp_user = BunnyPHP::app()->get('tp_user');
        if ($this->_mode == BunnyPHP::MODE_NORMAL) {
            if ($post['extra'] != '') {
                $extra = json_decode($post['extra'], true);
                if ($extra['type'] == 'paid' and $post['username'] != $tp_user['username'] and !(new PostPayModel())->check($tp_user['uid'], $post['tid'])) {
                    $balance = doubleval((new CreditModel())->balance($tp_user['uid']));
                    if ($balance == -1) {
                        $this->redirect('pay', 'start');
                        return;
                    }
                    $this->assign('coin', $extra['price']);
                    $this->assign('balance', $balance);
                    $this->assign('tid', $tid);
                    $this->assign('title', $post['title']);
                    if ($this->_mode == BunnyPHP::MODE_NORMAL) {
                        $this->assign('tp_user', $tp_user);
                        $this->assign('csrf_token', BunnyPHP::app()->get('csrf_token'));
                    }
                    $this->render('post/buy.html');
                    return;
                }
            }
            $this->assign('ret', 5004)->assign('status', 'no need to pay')->assign('tp_error_msg', "帖子不需要支付")
                ->render('common/error.html');
        }
    }

    /**
     * @filter csrf check
     * @filter auth canPay
     * @param array $path
     */
    function ac_buy_post(array $path)
    {
        $tid = isset($_REQUEST['tid']) ? $_REQUEST['tid'] : (isset($path[0]) ? $path[0] : 0);
        $post = (new PostModel())->getPostById($tid);
        $tp_user = BunnyPHP::app()->get('tp_user');
        if ($post['extra'] != '') {
            $extra = json_decode($post['extra'], true);
            if ($extra['type'] == 'paid' and $post['username'] != $tp_user['username'] and !(new PostPayModel())->check($tp_user['uid'], $post['tid'])) {
                $author = (new UserModel())->getUserByUsername($post['username']);
                if ((new CreditModel())->transfer($tp_user['uid'], $author['uid'], doubleval($extra['price']))) {
                    (new PostPayModel())->pay($tp_user['uid'], $post['tid']);
                    $this->assign('ret', 0);
                    $this->assign('status', 'ok');
                    $this->assign('tid', $tid);
                    if ($this->_mode == BunnyPHP::MODE_NORMAL) {
                        $this->assign('tp_user', $tp_user);
                    }
                    $this->render('post/buy.html');
                } else {
                    $this->assign('ret', 5003)->assign('status', 'no enough coin')->assign('tp_error_msg', "余额不足")
                        ->render('common/error.html');
                }
            }
        }
    }
}