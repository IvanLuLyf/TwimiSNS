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
            <h2><?= $err_msg ?? '' ?></h2>
            <a href="/install" class="btn btn-dark">重试</a>
        </div>
    </div>
</div>
</body>
</html>