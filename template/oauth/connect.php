<html lang="zh">
<head>
    <title>账号绑定 - <?= constant('TP_SITE_NAME') ?></title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
</head>
<body>
<?php include APP_PATH . 'template/common/navbar.php'; ?>
<div class="container">
    <div class="row">
        <div class="col-lg-3">
            <?php include APP_PATH . 'template/setting/nav.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="card neo_card mt-4">
                <div class="card-body">
                    <ul id="selTab" class="nav nav-tabs">
                        <?php if (isset($allow_reg)): ?>
                            <li class="nav-item">
                                <a class="nav-link active" href="#acReg" data-toggle="tab" aria-expanded="true">注册</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?= isset($allow_reg) ? '' : 'active' ?>" href="#acLogin"
                               data-toggle="tab" aria-expanded="false">已有账号</a>
                        </li>
                    </ul>
                    <div class="col-lg-12 mt-4">
                        <?php if (isset($tp_error_msg)): ?>
                            <div id="err_alert" class="alert alert-danger login-alert">
                                <a href="#" class="close" data-dismiss="alert">&times;</a>
                                <strong>提示信息:</strong><?= $tp_error_msg; ?>
                            </div>
                        <?php endif; ?>
                        <div id="selTabContent" class="tab-content">
                            <?php if (isset($allow_reg)): ?>
                                <div class="tab-pane in active" id="acReg">
                                    <form action="/oauth/bind/<?= $oauth['type'] ?>?bind_type=reg" method="post">
                                        <div class="form-group">
                                            <label for="reg_username">用户名</label>
                                            <input type="text" id="reg_username" name="username"
                                                   class="form-control" placeholder="用户名" required autofocus>
                                        </div>
                                        <div class="form-group">
                                            <label for="reg_password">密码</label>
                                            <input type="password" id="reg_password" name="password"
                                                   class="form-control"
                                                   placeholder="密码" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="reg_email">邮箱</label>
                                            <input type="email" id="reg_email" name="email" value=""
                                                   class="form-control"
                                                   placeholder="邮箱" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="reg_nickname">昵称</label>
                                            <input type="text" id="reg_nickname" name="nickname"
                                                   value="<?= $oauth['nickname'] ?>" class="form-control"
                                                   placeholder="昵称" required>
                                        </div>
                                        <input type="submit" class="btn btn-success btn-block" value="注册"/>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <div class="tab-pane <?= isset($allow_reg) ? '' : 'in active' ?>" id="acLogin">
                                <form action="/oauth/bind/<?= $oauth['type'] ?>?bind_type=login" method="post">
                                    <div class="form-group">
                                        <label for="username">用户名</label>
                                        <input type="text" id="username" name="username" class="form-control"
                                               placeholder="用户名" required autofocus>
                                    </div>
                                    <div class="form-group">
                                        <label for="password">密码</label>
                                        <input type="password" id="password" name="password" class="form-control"
                                               placeholder="密码" required>
                                    </div>
                                    <input type="submit" class="btn btn-success btn-block" value="登录"/>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include APP_PATH . 'template/common/footer.php'; ?>
</body>
</html>