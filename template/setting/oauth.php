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
                    <ul id="selTab" class="nav nav-tabs mb-2">
                        <?php foreach ($oauth_list as $o): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($oauth['type'] == $o[0]) ? 'active' : '' ?>"
                                   href="/setting/oauth/<?= $o[0] ?>"><?= $o[1] ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (isset($tp_bind)): ?>
                        <form action="/setting/oauth_avatar/<?= $oauth['type'] ?>">
                            <input type="hidden" name="avatar" value="<?= $avatar ?>">
                            <div class="form-group">
                                <div class="col-sm-3 col-form-label">
                                    <img height="100px" width="100px" class="rounded-circle"
                                         src="<?= $avatar ?>">
                                </div>
                                <div class="col-sm-9">
                                    <input type="submit" value="使用<?= $oauth['name'] ?>头像" class="btn btn-dark">
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <a role="button" class="btn btn-info"
                           href="/oauth/connect/<?= $oauth['type'] ?>">使用<?= $oauth['name'] ?>账号绑定</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include APP_PATH . 'template/common/footer.php'; ?>
</body>
</html>