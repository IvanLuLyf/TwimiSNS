<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <title>安装TwimiSNS</title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h3>TwimiSNS</h3>
        </div>
        <div class="card-body">
            <h2>站点配置</h2>
            <form method="post" action="/install/step3">
                <div class="form-group">
                    <label for="site_name">站点名称</label>
                    <input type="text" name="site_name" class="form-control" id="site_name" placeholder="站点名称" required>
                </div>
                <div class="form-group">
                    <label for="site_url">站点地址</label>
                    <input type="text" name="site_url" class="form-control" id="site_url" placeholder="站点名称"
                           value="<?= $_SERVER['HTTP_HOST'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">管理员用户名</label>
                    <input type="text" name="username" class="form-control" id="username" placeholder="用户名" required>
                </div>
                <div class="form-group">
                    <label for="email">邮箱</label>
                    <input type="email" name="email" class="form-control" id="email" placeholder="邮箱" required>
                </div>
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" name="password" class="form-control" id="password" placeholder="密码">
                </div>
                <div class="form-group">
                    <label for="nickname">昵称</label>
                    <input type="text" name="nickname" class="form-control" id="nickname" placeholder="昵称(可选)">
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input name="allow_reg" class="form-check-input" type="checkbox" id="allow_reg" value=""
                               checked>
                        <label class="form-check-label" for="allow_reg">
                            允许注册
                        </label>
                    </div>
                </div>
                <input type="submit" class="btn btn-dark" value="安装">
            </form>
        </div>
    </div>
</div>
</body>
</html>