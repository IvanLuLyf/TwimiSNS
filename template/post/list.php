<html lang="zh">
<head>
    <title><?= constant('TP_SITE_NAME') ?></title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
    <link href="/static/css/article.css" rel="stylesheet">
</head>
<body>
<?php include APP_PATH . 'template/common/navbar.php'; ?>
<div class="container">
    <div class="row">
        <div class="col-lg-9">
            <?php foreach ($posts as $post): ?>
                <div class="card neo_card mt-4">
                    <div class="card-body">
                        <div class="media">
                            <div class="media-body small">
                                <h5><a class="link-dark" href="/post/view/<?= $post['tid'] ?>"><?= $post['title'] ?></a>
                                </h5>
                                <div>
                                    <img class="rounded-circle"
                                         src="/user/avatar?username=<?= $post['username'] ?>"
                                         width="24px" alt="avatar"/>
                                    <span class="username">
									<a href="/user/detail/<?= $post['username'] ?>"
                                       class="text-muted font-weight-bold"><?= $post['nickname'] ?></a>
								</span>
                                    <span class="text-grey ml-2"><?= date('Y-m-d H:i:s', $post['timestamp']) ?></span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="markdown-body">
                            <?= strip_tags($parser->makeHtml($post['content'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
            <nav class="mt-4" aria-label="">
                <ul class="pagination justify-content-center">
                    <li class="page-item">
                        <a class="page-link" href="/post/list/1">首页</a>
                    </li>
                    <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                        <a aria-label="Previous" class="page-link"
                           href="/post/list/<?= $page == 1 ? 1 : ($page - 1) ?>">
                            <span aria-hidden="true">&laquo;</span>
                            <span class="sr-only">上一页</span>
                        </a>
                    </li>
                    <?php for ($i = ($page > 4 ? ($page - 3) : 1); ($i <= $page + 5 && $i <= $end_page); $i++): ?>
                        <li class="page-item <?= $page == $i ? 'active' : '' ?>"><a class="page-link"
                                                                                    href="/post/list/<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page == $end_page ? 'disabled' : '' ?>">
                        <a aria-label="Next" class="page-link"
                           href="/post/list/<?= $page == $end_page ? $end_page : ($page + 1) ?>">
                            <span aria-hidden="true">&raquo;</span>
                            <span class="sr-only">下一页</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="/post/list/<?= $end_page ?>">尾页</a>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="col-lg-3">
            <?php if (isset($tp_user)): ?>
                <div class="card neo_card mt-4">
                    <div class="card-body">
                        <a href="/post/create" class="btn btn-dark btn-block text-white badge-pill">发贴</a>
                    </div>
                </div>
                <div class="card neo_card mt-4">
                    <div class="card-body text-center">
                        <img src="/user/avatar/<?= $tp_user['uid'] ?>"
                             class="rounded-circle center-block" width="72px"/>
                        <h4 class="card-title"><?= $tp_user['nickname'] ?></h4>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (isset($recommend_posts)): ?>
                <div class="card neo_card mt-4">
                    <div class="card-header">
                        推荐列表
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recommend_posts as $post): ?>
                            <li class="list-group-item list-group-item-action"><?= $post['title'] ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php if (isset($tp_user)): ?>
    <div class="fab-div d-lg-none d-md-none d-xl-none">
        <button class="btn btn-success btn-fab" onclick="window.location.href='/post/create'">+</button>
    </div>
<?php endif; ?>
<?php include APP_PATH . 'template/common/footer.php'; ?>
</body>
</html>