<html lang="zh">
<head>
    <title>发帖 - <?= constant('TP_SITE_NAME') ?></title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
    <script src="https://cdn.bootcss.com/showdown/1.8.6/showdown.min.js"></script>
    <link href="/static/css/article.css" rel="stylesheet">
</head>
<body>
<?php include APP_PATH . 'template/common/navbar.php'; ?>
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <div class="card neo_card mt-4">
                <div class="card-body">
                    <form class="form-horizontal" role="form" action="/post/create" method="post">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"/>
                        <div class="form-group">
                            <label for="title">标题</label>
                            <input id="title" name="title" type="text" placeholder="标题" class="form-control" required/>
                        </div>
                        <div class="form-row">
                            <div class="col-lg-6 form-group">
                                <label for="content">内容</label>
                                <textarea class="form-control" id="content" name="content" rows="15" cols="60"
                                          placeholder="内容" required oninput="markdownIt()"></textarea>
                            </div>
                            <div class="col-lg-6 form-group">
                                <label for="preview">预览</label>
                                <div class="form-control markdown-body" style="height: 20rem;overflow:auto"
                                     id="preview"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="float-right">
                                <button class="btn btn-outline-dark" type="submit">发布</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include APP_PATH . 'template/common/footer.php'; ?>
<script>
    var converter = new showdown.Converter();

    function markdownIt() {
        $('#preview').html(converter.makeHtml($('#content').val()))
    }
</script>
</body>
</html>