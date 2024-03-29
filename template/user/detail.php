<html lang="zh">
<head>
    <title><?= constant('TP_SITE_NAME') ?></title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
    <link href="/static/css/article.css" rel="stylesheet">
    <?php if (!empty($user_info['background'])): ?>
        <style>
            body {
                background: url(<?=$user_info[ 'background' ]?>) no-repeat fixed center 0;
            }
        </style>
    <?php endif; ?>
</head>
<body>
<?php include APP_PATH . 'template/common/navbar.php'; ?>
<div class="container">
    <div class="row">
        <div class="col-lg-9">
            <div class="card neo_card mt-4">
                <div class="card-body text-center">
                    <img src="/user/avatar/<?= $user['uid'] ?>" class="rounded-circle center-block" width="72px"
                         alt="avatar"/>
                    <h4 class="card-title"><?= $user['nickname'] ?></h4>
                    <p class="card-text"><?= htmlspecialchars($user_info['signature'] ?? '') ?></p>
                </div>
            </div>
            <?php if (!empty($tab) && $tab == 'post'): ?>
                <div class="card neo_card mt-4">
                    <div class="card-body">
                        <?php foreach ($posts as $post): ?>
                            <div class="media">
                                <div class="media-body small">
                                    <h5><a class="link-dark"
                                           href="/post/view/<?= $post['tid'] ?>"><?= $post['title'] ?></a>
                                    </h5>
                                    <div>
                                        <img class="rounded-circle"
                                             src="/user/avatar?username=<?= $post['username'] ?>"
                                             width="24px"/>
                                        <span class="username">
									<a href="/user/detail/<?= $post['username'] ?>"
                                       class="text-muted font-weight-bold"><?= $user['nickname'] ?></a>
								</span>
                                        <span class="text-grey ml-2"><?= date('Y-m-d H:i:s', $post['timestamp']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <hr>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-3">
            <?php if (isset($recommend_blogs)): ?>
                <div class="card neo_card mt-4">
                    <div class="card-header">
                        推荐列表
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recommend_blogs as $blog): ?>
                            <li class="list-group-item list-group-item-action"><?= $blog['title'] ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include APP_PATH . 'template/common/footer.php'; ?>
</body>
</html>