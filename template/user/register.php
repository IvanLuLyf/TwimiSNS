<html lang="zh">
<head>
    <title>注册</title>
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
    <div class="card form-login">
        <form action="/user/register" method="post">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="用户名" required
                       autofocus>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="密码" required>
            </div>
            <div class="form-group">
                <label for="email">邮箱</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="邮箱" required>
            </div>
            <div class="form-group">
                <label for="nickname">昵称</label>
                <input type="text" id="nickname" name="nickname" class="form-control" placeholder="昵称(可选)">
            </div>
            <?php if (isset($referer)): ?>
                <input type="hidden" id="referer" name="referer" value="<?= $referer ?>">
            <?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"/>
            <input type="submit" class="btn btn-success btn-block" value="注册">
            <div class="mt-2 float-right"><a
                        href="/user/login?referer=<?= isset($referer) ? urlencode($referer) : '' ?>">登录</a></div>
        </form>
    </div>
</div>
</body>
</html>