<html lang="zh-cn">
<head>
    <title><?= constant("TP_SITE_NAME") ?></title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
</head>
<body>
<?php include APP_PATH . 'template/common/navbar.php'; ?>
<div class="container">
    <div class="row">
        <div class="col-lg-9">
            <div class="card neo_card mt-4">
                <div class="card-body">
                    <h1 class="card-title"><?= $tp_error_msg ?></h1>
                    <a class="btn btn-dark text-white mt-5" href="/" role="button">返回首页</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include APP_PATH . 'template/common/footer.php'; ?>
</body>
</html>