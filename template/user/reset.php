<html lang="zh">
<head>
    <title><?= constant("TP_SITE_NAME") ?></title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
</head>
<body>
<?php include APP_PATH . 'template/common/navbar.php'; ?>
<div class="container">
    <div class="row">
        <div class="col-lg-7">
            <div class="card neo_card mt-4">
                <div class="card-body">
                    <h3 class="card-title">重设密码</h3>
                    <?php if (isset($tp_error_msg)): ?>
                        <div id="err_alert" class="alert alert-danger login-alert">
                            <a href="#" class="close" data-dismiss="alert">&times;</a>
                            <strong>提示信息:</strong><?= $tp_error_msg; ?>
                        </div>
                    <?php endif; ?>
                    <form class="form-horizontal" role="form" action="/user/reset"
                          method="post">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"/>
                        <input type="hidden" name="code" value="<?= $code ?>"/>
                        <div class="form-group">
                            <label for="password">密码</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="密码" required/>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="mr-auto"></div>
                                <div class="col-md-3 col-lg-3">
                                    <button class="btn btn-dark btn-block" type="submit">重设密码</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include APP_PATH . 'template/common/footer.php'; ?>
</body>
</html>