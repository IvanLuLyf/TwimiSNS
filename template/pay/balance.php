<html lang="zh-cn">
<head>
    <title><?= constant('TP_SITE_NAME') ?></title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
</head>
<body>
<?php include APP_PATH . 'template/common/navbar.php'; ?>
<div class="container">
    <div class="row">
        <div class="col-lg-9">
            <div class="card neo_card mt-4">
                <div class="card-body">
                    <?php if ($credit != -1): ?>
                        账户余额: <?= $credit ?> 个硬币
                    <?php else: ?>
                        <a href="/pay/start" role="button" class="btn btn-dark">开通<?= TP_SITE_NAME ?>支付</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include APP_PATH . 'template/common/footer.php'; ?>
</body>
</html>