<nav class="navbar navbar-expand-sm navbar-dark bg-dark fixed-top">
    <a class="navbar-brand" href="/index/index"><?= constant("TP_SITE_NAME") ?></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link <?= (isset($cur_ctr) && $cur_ctr == 'post') ? 'active' : '' ?>"
                   href="/post/index">帖子</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($cur_ctr) && $cur_ctr == 'search') ? 'active' : '' ?>"
                   href="/post/search">搜索</a>
            </li>
        </ul>
        <ul class="navbar-nav">
            <?php if (isset($tp_user)): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/setting/index">
                        <img src="/user/avatar/<?= $tp_user['uid'] ?>"
                             class="rounded-circle" width="32"> <?= $tp_user['nickname'] ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/user/logout">退出</a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="/user/login">登录</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/user/register">注册</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>