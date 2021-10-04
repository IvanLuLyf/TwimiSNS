<html lang="zh-cn">
<head>
    <meta property="og:title" content="<?= constant('TP_SITE_NAME') ?>">
    <meta property="og:image" content="/user/avatar?username=<?= $post['username'] ?>">
    <meta property="og:description" content="<?= $post['title'] ?>">
    <title><?= $post['title'] ?> - <?= constant('TP_SITE_NAME') ?></title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
    <link href="/static/css/article.css" rel="stylesheet">
</head>
<body>
<?php include APP_PATH . 'template/common/navbar.php'; ?>
<div class="container">
    <div class="row">
        <div class="col-lg-9">
            <div class="card neo_card mt-4">
                <div class="card-body">
                    <h3><?= $post['title'] ?></h3>
                    <div class="media">
                        <img class="rounded-circle"
                             src="/user/avatar?username=<?= $post['username'] ?>"
                             width="48px" alt="avatar"/>
                        <div class="media-body ml-3 small">
                            <h6><a href="/user/detail/<?= $post['username'] ?>"
                                   class="text-muted font-weight-bold"><?= $post['nickname'] ?></a></h6>
                            <div>
                                <span class="text-grey text-muted font-weight-bold"><?= date('Y-m-d H:i:s', $post['timestamp']) ?></span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <?php if ($show_state == 0): ?>
                        <div class="markdown-body">
                            <?= $html_content ?>
                        </div>
                    <?php elseif ($show_state == 1): ?>
                        <p>该帖子需要付费阅读,请支付<?= $coin ?>硬币之后阅读<a role="button" class="btn btn-dark"
                                                             href="/post/buy/<?= $post['tid'] ?>">购买</a></p>
                    <?php elseif ($show_state == 2): ?>
                        <p>该帖子需要登录才可继续阅读,请登陆后继续阅读</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card neo_card mt-3">
                <div class="card-body">
                    <?php if (isset($tp_user)): ?>
                        <form class="form-horizontal" role="form" action="/post/comment/<?= $post['tid'] ?>"
                              method="post">
                            <input type="hidden" name="tid" value="<?= $post['tid'] ?>"/>
                            <div class="form-group">
                                <label for="content">评论</label>
                                <textarea class="form-control" id="content" name="content" rows="3"
                                          cols="60"
                                          placeholder="内容" required></textarea>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="mr-auto"></div>
                                    <div class="col-md-3 col-lg-3">
                                        <button class="btn btn-dark btn-block badge-pill" type="submit">发布</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="center-block">
                            <p class="text-center">
                                <a href="/user/login?referer=/post/view/<?= $post['tid'] ?>">登录</a>评论
                            </p>
                        </div>
                        <div class="oauth-bar text-center">
                            <?php foreach ($oauth as $o): ?>
                                <a href="/oauth/connect/<?= $o[0] ?>?referer=/post/view/<?= $post['tid'] ?>"><img
                                            alt="<?= $o[1] ?>"
                                            class="oauth-icon"
                                            src="/static/img/<?= $o[0] ?>.png"></a>
                            <?php endforeach ?>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($comments as $comment) : ?>
                        <hr>
                        <div class="media">
                            <img class="rounded-circle"
                                 src="/user/avatar?username=<?= $comment['username'] ?>"
                                 width="40px" alt="avatar"/>
                            <div class="media-body ml-2 small">
                                <div>
								<span class="username">
									<a href="/user/detail/<?= $comment['username'] ?>"
                                       class="text-muted font-weight-bold"><?= $comment['nickname'] ?></a>
								</span>
                                    <span class="text-grey ml-2"><?= date('Y-m-d H:i:s', $comment['timestamp']) ?></span>
                                </div>
                                <h6><?= htmlspecialchars($comment['content']) ?></h6>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include APP_PATH . 'template/common/footer.php'; ?>
</body>
</html>