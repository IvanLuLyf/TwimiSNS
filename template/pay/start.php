<html lang="zh-cn">
<head>
    <title><?= constant("TP_SITE_NAME") ?></title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
    <style>
        input[type="tel" i].password {
            -webkit-text-security: disc;
        }
    </style>
</head>
<body>
<?php include APP_PATH . 'template/common/navbar.php'; ?>
<div class="container">
    <div class="row">
        <div class="col-lg-9">
            <div class="card neo_card mt-4">
                <div class="card-body">
                    <?php if (isset($ret) && $ret == 0): ?>
                        <div class="alert alert-success" role="alert">
                            <?= TP_SITE_NAME ?>支付开通成功
                        </div>
                        <a href="/pay/balance" role="button" class="btn btn-dark">返回</a>
                    <?php else: ?>
                        <form class="form-horizontal" role="form" action="/pay/start"
                              method="post">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"/>
                            <div class="form-group">
                                <label for="pass">密码</label>
                                <input type="tel" class="form-control password" id="pass" name="pass" maxlength="6"
                                       placeholder="密码" required/>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="mr-auto"></div>
                                    <div class="col-md-3 col-lg-3">
                                        <button class="btn btn-dark btn-block" type="submit">设置支付密码</button>
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