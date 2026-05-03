<?php

use BunnyPHP\BunnyPHP;
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

    /** @return array{ok: bool, extra: string, err?: array} */
    private function extraFromVisibilityPost(): array
    {
        $visibility = $_POST['visibility'] ?? 'public';
        if ($visibility === 'login') {
            return ['ok' => true, 'extra' => json_encode(['type' => 'login'], JSON_UNESCAPED_UNICODE)];
        }
        if ($visibility === 'paid') {
            $price = floatval($_POST['price'] ?? 0);
            if ($price <= 0) {
                return [
                    'ok' => false,
                    'err' => ['ret' => -7, 'status' => 'invalid price', 'tp_error_msg' => '付费帖子请填写大于 0 的价格'],
                ];
            }

            return ['ok' => true, 'extra' => json_encode(['type' => 'paid', 'price' => $price], JSON_UNESCAPED_UNICODE)];
        }

        return ['ok' => true, 'extra' => ''];
    }

    /** @return array|null */
    private function verifyBuyerPayPass(array $tp_user): ?array
    {
        $pass = trim((string) ($_POST['pass'] ?? ''));
        $len = function_exists('mb_strlen') ? mb_strlen($pass) : strlen($pass);
        if ($len < 1 || $len > 6) {
            return ['ret' => -7, 'status' => 'invalid pass', 'tp_error_msg' => '支付密码为 1～6 位'];
        }
        $stored = (new PayPassModel())->getPassword($tp_user['uid']);
        if ($stored === null || $stored === '') {
            return ['ret' => 5008, 'status' => 'pay password not set', 'tp_error_msg' => '请先开通钱包并设置支付密码'];
        }
        if (!hash_equals($stored, md5($pass))) {
            return ['ret' => 5001, 'status' => 'wrong payment password', 'tp_error_msg' => '支付密码错误'];
        }

        return null;
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
        $this->render('app.php');
    }

    /**
     * @filter csrf check
     * @filter auth feed
     * @param string $title not_empty()
     * @param string $content not_empty()
     */
    public function ac_create_post(string $title, string $content)
    {
        $pack = $this->extraFromVisibilityPost();
        if (!$pack['ok']) {
            $this->assignAll($pack['err'])->error();

            return;
        }
        $tid = $this->postModel->sendPost(BunnyPHP::app()->get('tp_user'), $title, $content, $pack['extra']);
        $this->redirect('post', 'view', ['tid' => $tid]);
    }

    /**
     * @param int $tid path(0)
     */
    public function ac_view(int $tid = 0)
    {
        if ($this->postModel->getPostById($tid) === null) {
            $this->assignAll(['ret' => 3001, 'status' => 'invalid tid', 'tp_error_msg' => '帖子不存在'])->error();
            return;
        }
        $this->render('app.php');
    }

    /**
     * @param UserService $userService
     * @param int $page path(0)
     */
    /**
     * @param int $page path(0)
     */
    function ac_list(int $page = 1)
    {
        $this->render('app.php');
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
            $this->redirect('post', 'view', ['tid' => $tid]);
            return;
        }
        $this->assignAll(['ret' => 3001, 'status' => 'invalid tid', 'tp_error_msg' => '帖子不存在'])->error();
    }

    function ac_search(string $word = '', int $page = 1, int $limit = 20)
    {
        $this->render('app.php');
    }

    /**
     * @filter csrf
     * @filter auth
     * @param int $tid path(0)
     */
    function ac_buy_get(int $tid = 0)
    {
        if ($this->postModel->getPostById($tid) === null) {
            $this->assignAll(['ret' => 3001, 'status' => 'invalid tid', 'tp_error_msg' => '帖子不存在'])->error();
            return;
        }
        $this->render('app.php');
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
        if ($post === null) {
            $this->assignAll(['ret' => 3001, 'status' => 'invalid tid', 'tp_error_msg' => '帖子不存在'])->error();

            return;
        }
        if ($post['extra'] != '') {
            $extra = json_decode($post['extra'], true);
            if ($extra['type'] == 'paid' and $post['username'] != $tp_user['username'] and !(new PostPayModel())->check($tp_user['uid'], $post['tid'])) {
                $passErr = $this->verifyBuyerPayPass($tp_user);
                if ($passErr !== null) {
                    $this->assignAll($passErr)->error();

                    return;
                }
                $author = (new UserModel())->getUserByUsername($post['username']);
                if ((new CreditModel())->transfer($tp_user['uid'], $author['uid'], doubleval($extra['price']))) {
                    (new PostPayModel())->pay($tp_user['uid'], $post['tid']);
                    $this->assignAll(['ret' => 0, 'status' => 'ok', 'tid' => $tid])->render('app.php');
                } else {
                    $this->assignAll(['ret' => 5003, 'status' => 'insufficient balance', 'tp_error_msg' => '余额不足'])->error();
                }
            }
        } else {
            $this->assignAll(['ret' => 5004, 'status' => 'no need to pay', 'tp_error_msg' => '帖子不需要支付'])->error();
        }
    }

    /**
     * @param UserService $userService
     * @param int $page path(0)
     */
    public function ac_feed(UserService $userService, int $page = 1): void
    {
        $tp_user = $userService->getLoginUser();
        $posts = $this->postModel->getPostByPage($page);
        $total = $this->postModel->getTotal();
        foreach ($posts as &$post) {
            PostsUtil::apply($post, $tp_user);
        }
        unset($post);
        $this->assignAll([
            'ret' => 0,
            'status' => 'ok',
            'total' => $total,
            'page' => $page,
            'posts' => array_values($posts),
            'end_page' => (int) ceil($total / 20),
        ])->render('app.php');
    }

    /**
     * @param UserService $userService
     * @param int $tid path(0)
     */
    public function ac_thread(UserService $userService, int $tid = 0): void
    {
        $post = $this->postModel->getPostById($tid);
        $tp_user = $userService->getLoginUser();
        if ($post === null) {
            $this->assignAll(['ret' => 3001, 'status' => 'invalid tid', 'tp_error_msg' => '帖子不存在'])->render('app.php');
            return;
        }
        $extraArr = json_decode($post['extra'] ?? '', true);
        $showState = PostsUtil::apply($post, $tp_user);
        $coin = null;
        if ($showState === 1 && is_array($extraArr) && isset($extraArr['price'])) {
            $coin = $extraArr['price'];
        }
        $comments = (new CommentModel())->listComment($tid, ConstUtil::MOD_FORUM, 1);
        $this->assignAll([
            'ret' => 0,
            'status' => 'ok',
            'show_state' => $showState,
            'coin' => $coin,
            'post' => $post,
            'comments' => $comments,
        ])->render('app.php');
    }

    public function ac_find(string $word, UserService $userService, int $page = 1): void
    {
        $tp_user = $userService->getLoginUser();
        if ($word !== '') {
            $result = $this->postModel->search($word, $page, 20);
            $endPage = (int) ceil($result['total'] / 20);
        } else {
            $result = ['total' => 0, 'posts' => []];
            $endPage = 0;
        }
        foreach ($result['posts'] as &$post) {
            PostsUtil::apply($post, $tp_user);
        }
        unset($post);
        $this->assignAll([
            'ret' => 0,
            'status' => 'ok',
            'word' => $word,
            'page' => $page,
            'total' => $result['total'],
            'end_page' => $endPage,
            'posts' => array_values($result['posts']),
        ])->render('app.php');
    }

    /**
     * @filter csrf check
     * @filter auth feed
     * @param int $tid path(0)
     */
    public function ac_reply_post(int $tid = 0): void
    {
        $tp_user = BunnyPHP::app()->get('tp_user');
        $post = $this->postModel->getPostById($tid);
        if ($post === null) {
            $this->assignAll(['ret' => 3001, 'status' => 'invalid tid', 'tp_error_msg' => '帖子不存在'])->render('app.php');
            return;
        }
        $content = $_POST['content'] ?? '';
        if ($content === '') {
            $this->assignAll(['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '内容不能为空'])->render('app.php');
            return;
        }
        (new CommentModel())->sendComment($tid, ConstUtil::MOD_FORUM, $tp_user, $content);
        $comments = (new CommentModel())->listComment($tid, ConstUtil::MOD_FORUM, 1);
        $this->assignAll(['ret' => 0, 'status' => 'ok', 'comments' => $comments])->render('app.php');
    }

    /**
     * @filter csrf check
     * @filter auth feed
     */
    public function ac_push_post(): void
    {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        if ($title === '' || $content === '') {
            $this->assignAll(['ret' => -7, 'status' => 'parameter cannot be empty', 'tp_error_msg' => '标题或内容不能为空'])->render('app.php');
            return;
        }
        $pack = $this->extraFromVisibilityPost();
        if (!$pack['ok']) {
            $this->assignAll($pack['err'])->render('app.php');
            return;
        }
        $tid = $this->postModel->sendPost(BunnyPHP::app()->get('tp_user'), $title, $content, $pack['extra']);
        $this->assignAll(['ret' => 0, 'status' => 'ok', 'tid' => $tid])->render('app.php');
    }

    /**
     * @param UserService $userService
     * @param int $tid path(0)
     */
    public function ac_pay_preview(UserService $userService, int $tid = 0): void
    {
        $tp_user = $userService->getLoginUser();
        if ($tp_user === null) {
            $this->assignAll(['ret' => 2002, 'status' => 'login required', 'tp_error_msg' => '请先登录'])->render('app.php');
            return;
        }
        $post = $this->postModel->getPostById($tid);
        if ($post === null) {
            $this->assignAll(['ret' => 3001, 'status' => 'invalid tid', 'tp_error_msg' => '帖子不存在'])->render('app.php');
            return;
        }
        if (($post['extra'] ?? '') === '') {
            $this->assignAll(['ret' => 5004, 'status' => 'no need to pay', 'tp_error_msg' => '该帖子无需付费'])->render('app.php');
            return;
        }
        $extra = json_decode($post['extra'], true);
        if (!is_array($extra) || ($extra['type'] ?? '') !== 'paid') {
            $this->assignAll(['ret' => 5004, 'status' => 'no need to pay', 'tp_error_msg' => '该帖子无需付费'])->render('app.php');
            return;
        }
        if ($post['username'] === $tp_user['username'] || (new PostPayModel())->check($tp_user['uid'], (int) $post['tid'])) {
            $this->assignAll(['ret' => 0, 'status' => 'ok', 'unlocked' => true, 'tid' => (int) $tid])->render('app.php');
            return;
        }
        $balance = doubleval((new CreditModel())->balance($tp_user['uid']));
        if ($balance == -1) {
            $this->assignAll([
                'ret' => 0,
                'status' => 'ok',
                'unlocked' => false,
                'need_wallet' => true,
                'tid' => (int) $tid,
                'title' => $post['title'],
                'price' => $extra['price'] ?? 0,
            ])->render('app.php');
            return;
        }
        $this->assignAll([
            'ret' => 0,
            'status' => 'ok',
            'unlocked' => false,
            'need_wallet' => false,
            'tid' => (int) $tid,
            'title' => $post['title'],
            'price' => $extra['price'] ?? 0,
            'balance' => $balance,
        ])->render('app.php');
    }

    /**
     * @filter csrf check
     * @param UserService $userService
     * @param int $tid path(0)
     */
    public function ac_pay_commit_post(UserService $userService, int $tid = 0): void
    {
        $tp_user = $userService->getLoginUser();
        if ($tp_user === null) {
            $this->assignAll(['ret' => 2002, 'status' => 'login required', 'tp_error_msg' => '请先登录'])->render('app.php');
            return;
        }
        $post = $this->postModel->getPostById($tid);
        if ($post === null) {
            $this->assignAll(['ret' => 3001, 'status' => 'invalid tid', 'tp_error_msg' => '帖子不存在'])->render('app.php');
            return;
        }
        if (($post['extra'] ?? '') === '') {
            $this->assignAll(['ret' => 5004, 'status' => 'no need to pay', 'tp_error_msg' => '该帖子无需付费'])->render('app.php');
            return;
        }
        $extra = json_decode($post['extra'], true);
        if (!is_array($extra) || ($extra['type'] ?? '') !== 'paid') {
            $this->assignAll(['ret' => 5004, 'status' => 'no need to pay', 'tp_error_msg' => '该帖子无需付费'])->render('app.php');
            return;
        }
        if ($post['username'] === $tp_user['username'] || (new PostPayModel())->check($tp_user['uid'], (int) $post['tid'])) {
            $this->assignAll(['ret' => 0, 'status' => 'ok', 'tid' => (int) $tid])->render('app.php');
            return;
        }
        $passErr = $this->verifyBuyerPayPass($tp_user);
        if ($passErr !== null) {
            $this->assignAll($passErr)->render('app.php');
            return;
        }
        $author = (new UserModel())->getUserByUsername($post['username']);
        if ($author === null || empty($author['uid'])) {
            $this->assignAll(['ret' => 3001, 'status' => 'invalid author', 'tp_error_msg' => '作者不存在'])->render('app.php');
            return;
        }
        $price = doubleval($extra['price'] ?? 0);
        if ((new CreditModel())->transfer($tp_user['uid'], (int) $author['uid'], $price)) {
            (new PostPayModel())->pay($tp_user['uid'], (int) $post['tid']);
            $this->assignAll(['ret' => 0, 'status' => 'ok', 'tid' => (int) $tid])->render('app.php');
            return;
        }
        $this->assignAll(['ret' => 5003, 'status' => 'insufficient balance', 'tp_error_msg' => '余额不足'])->render('app.php');
    }
}