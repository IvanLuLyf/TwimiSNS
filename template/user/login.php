<html lang="zh">
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
    <div class="card form-login">
        <form action="/user/login" method="post">
            <div class="form-group">
                <label for="username"><?= BunnyPHP\Language::get('username') ?></label>
                <input type="text" id="username" name="username" class="form-control"
                       placeholder="<?= BunnyPHP\Language::get('username') ?>" required
                       autofocus>
            </div>
            <div class="form-group">
                <label for="password"><?= BunnyPHP\Language::get('password') ?></label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="<?= BunnyPHP\Language::get('password') ?>" required>
            </div>
            <?php if (isset($referer)): ?>
                <input type="hidden" id="referer" name="referer" value="<?= $referer ?>">
            <?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"/>
            <input type="submit" class="btn btn-success btn-block" value="<?= BunnyPHP\Language::get('login') ?>">
            <div class="oauth-bar">
                <?php foreach ($oauth as $o): ?>
                    <a href="/oauth/connect/<?= $o[0] ?>"><img alt="<?= $o[1] ?>" class="oauth-icon"
                                                               src="/static/img/<?= $o[0] ?>.png"></a>
                <?php endforeach ?>
            </div>
            <div class="float-right"><a
                        href="/user/register?referer=<?= isset($referer) ? urlencode($referer) : '' ?>"><?= BunnyPHP\Language::get('register') ?></a>
                | <a href="/user/forgot"><?= BunnyPHP\Language::get('forgot_password') ?></a></div>
        </form>
    </div>
</div>
</body>
</html>