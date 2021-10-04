<html lang="zh">
<head>
    <title><?= constant('TP_SITE_NAME') ?></title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
</head>
<body>
<?php include APP_PATH . 'template/common/navbar.php'; ?>
<div class="container">
    <div class="row">
        <div class="col-lg-7">
            <div class="card neo_card mt-4">
                <div class="card-body">
                    <h3 class="card-title">帖子支付</h3>
                    <?php if (isset($ret) and $ret == 0): ?>
                        <div class="alert alert-success" role="alert">
                            支付成功
                        </div>
                        <a href="/post/view/<?= $tid ?>" role="button" class="btn btn-dark">返回</a>
                    <?php else: ?>
                        <form class="form-horizontal" role="form" action="/post/buy/<?= $tid ?>"
                              method="post">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"/>
                            <p>您是否确认为帖子“<?= $title ?>”支付<?= $coin ?>个硬币</p>
                            <p>当前余额: <?= $balance ?></p>
                            <div class="form-group">
                                <div class="row">
                                    <div class="mr-auto"></div>
                                    <div class="col-md-3 col-lg-3">
                                        <button class="btn btn-dark btn-block" type="submit">支付</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include APP_PATH . 'template/common/footer.php'; ?>
</body>
</html>