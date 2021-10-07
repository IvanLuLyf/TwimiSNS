<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= TP_SITE_NAME ?></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.1/js/bootstrap.min.js"></script>
    <script src="https://cdn.bootcss.com/showdown/1.8.6/showdown.min.js"></script>
    <link href="/static/css/common.css?v=20211007" rel="stylesheet">
    <link href="/static/css/article.css?v=20211007" rel="stylesheet">
    <script src="/static/js/elaina.js"></script>
    <script src="/static/js/util.js"></script>
</head>
<body>
<div id="app"></div>
<script>
    (function () {
        var prevPath = '', isMain = false;
        Elaina.data({
            siteName: '<?=TP_SITE_NAME?>',
            apiUrl: '/api/',
        });
        Elaina.configure({
            pages: '/static/page',
            widgets: '/static/widget',
            traits: '/static/trait',
            debug: true,
            scope: '/sns/',
            version: '0.0.1',
        }).router(function (pageInfo) {
            if (pageInfo.path !== prevPath) {
                prevPath = pageInfo.path;
                if (pageInfo.path === '/login' || pageInfo.path === '/register') {
                    isMain = false;
                    Elaina.render(Elaina.el, pageInfo.path);
                } else {
                    if (!isMain) {
                        Elaina.render(Elaina.el, '/main');
                        isMain = true;
                    } else {
                        Elaina.render($('#router_container'), pageInfo.path);
                    }
                }
            }
        }).mount('#app');
    })();
</script>
</body>
</html>