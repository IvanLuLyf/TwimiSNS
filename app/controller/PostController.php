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
     * @filter auth
     */
    public function ac_create_get()
    {
        $this->assign('tp_user', BunnyPHP::app()->get('tp_user'));
        $this->render('post/create.html');
    }

    /**
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
        if ($post != null) {
            $comments = (new CommentModel())->listComment($tid, 1);
            if ($this->_mode == BunnyPHP::MODE_NORMAL) {
                $this->assign('tp_user', $tp_user);
                $this->assign('cur_ctr', 'post');
                include APP_PATH . 'library/Parser.php';
                $parser = new HyperDown\Parser;
                $html_content = $parser->makeHtml($post['content']);
                $this->assign("html_content", $html_content);
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
        $total = $postModel->getTotal();
        $endPage = ceil($total / 20);
        if ($this->_mode == BunnyPHP::MODE_NORMAL) {
            include APP_PATH . 'library/Parser.php';
            $parser = new HyperDown\Parser;
            $this->assign('parser', $parser);
            $this->assign('tp_user', $userService->getLoginUser())
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
}