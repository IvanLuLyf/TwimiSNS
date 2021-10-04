<html lang="zh">
<head>
    <title><?= constant("TP_SITE_NAME") ?></title>
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
                    搜索
                    <form class="mt-2 mb-2 row" action="/post/search">
                        <div class="input-group mb-3 col-lg-6">
                            <input name="word" class="form-control" type="text" placeholder="搜索" aria-label="搜索"
                                   value="<?= $word ?>">
                            <div class="input-group-append">
                                <input type="submit" class="btn btn-outline-secondary" value="搜索">
                            </div>
                        </div>
                    </form>
                    <?php if ($total == 0): ?>
                        <?php if ($word != ''): ?>
                            找不到帖子
                        <?php endif; ?>
                    <?php else: ?>
                        共找到<?= $total ?>篇帖子
                    <?php endif; ?>
                </div>
            </div>
            <?php foreach ($posts as $post): ?>
                <div class="card neo_card mt-4">
                    <div class="card-body">
                        <div class="media">
                            <div class="media-body small">
                                <h4><a class="link-dark" href="/post/view/<?= $post['tid'] ?>"><?= $post['title'] ?></a>
                                </h4>
                                <div>
								<span class="username">
									<a href="/user/detail/<?= $post['username'] ?>"
                                       class="text-muted font-weight-bold"><?= $post['nickname'] ?></a>
								</span>
                                    <span class="text-grey ml-2"><?= date('Y-m-d H:i:s', $post['timestamp']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
            <?php if ($total != 0): ?>
                <nav class="mt-4" aria-label="">
                    <ul class="pagination justify-content-center">
                        <li class="page-item">
                            <a class="page-link link-dark" href="/post/search?word=<?= $word ?>&page=1">首页</a>
                        </li>
                        <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                            <a aria-label="Previous" class="page-link link-dark"
                               href="/post/search?word=<?= $word ?>&page=<?= $page == 1 ? 1 : ($page - 1) ?>">
                                <span aria-hidden="true">&laquo;</span>
                                <span class="sr-only">上一页</span>
                            </a>
                        </li>
                        <?php for ($i = ($page > 4 ? ($page - 3) : 1); ($i <= $page + 5 && $i <= $end_page); $i++): ?>
                            <li class="page-item <?= $page == $i ? 'active' : '' ?>"><a class="page-link link-dark"
                                                                                        href="/post/search?word=<?= $word ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page == $end_page ? 'disabled' : '' ?>">
                            <a aria-label="Next" class="page-link link-dark"
                               href="/post/search?word=<?= $word ?>&page=<?= $page == $end_page ? $end_page : ($page + 1) ?>">
                                <span aria-hidden="true">&raquo;</span>
                                <span class="sr-only">下一页</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link link-dark" href="/post/search?word=<?= $word ?>&page=<?= $end_page ?>">尾页</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include APP_PATH . 'template/common/footer.php'; ?>
</body>
</html>