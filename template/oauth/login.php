<html>
<head>
    <title>登录</title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
    <link rel="stylesheet" href="/static/css/login.css"/>
</head>
<body>
<div class="container">
    <h2 class="login-heading"><img src="/static/img/logo.png" height="96px" alt="LOGO"></h2>
    <?php if (isset($tp_error_msg)): ?>
        <div id="err_alert" class="alert alert-danger login-alert">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
            <strong>提示信息:</strong><?= $tp_error_msg; ?>
        </div>
    <?php endif; ?>
    <?php if (!isset($tp_hide)): ?>
        <div class="card form-login">
            <form action="/oauth/authorize" method="post">
                <h5 class="login-heading">是否允许<a href="http://<?= $app_url ?>"><?= $client_name ?></a>访问你的账号</h5>
                <input type="hidden" name="client_id" value="<?= $client_id ?>">
                <input type="hidden" name="redirect_uri" value="<?= $redirect_uri ?>">
                <?php if ($tp_user == null): ?>
                    <div class="form-group">
                        <label for="username">用户名</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="用户名" required
                               autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">密码</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="密码"
                               required>
                    </div>
                    <input type="hidden" id="referer" name="referer" value="<?= $referer ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"/>
                    <input type="submit" class="btn btn-success btn-block" value="登录">
                    <div class="float-right"><a
                                href="/user/register?referer=<?= isset($referer) ? urlencode($referer) : '' ?>">注册</a>
                        | <a href="/user/forgot">忘记密码</a></div>
                <?php else: ?>
                    <p class="text-center">
                        <img class="rounded-circle" height="80px"
                             src="/user/avatar?username=<?= $tp_user['username'] ?>"/>
                        <img src="/static/img/arrow.png" height="60px">
                        <img class="rounded-circle" height="80px" src="<?= $client_icon ?>"/>
                    </p>
                    <h5 class="login-heading">当前用户:<?= $tp_user["nickname"]; ?></h5>
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"/>
                    <input type="submit" class="btn btn-success btn-block" value="授权">
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>