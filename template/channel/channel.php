<html lang="zh-cn">
<head>
    <title>频道 - <?= constant("TP_SITE_NAME") ?></title>
    <?php include "template/common/header.html"; ?>
    <link href="/static/css/article.css" rel="stylesheet">
</head>
<body>
<?php include "template/common/navbar.html"; ?>
<div class="container">
    <div class="row">
        <div class="col-lg-9">
            <div class="card neo_card mt-4">
                <div class="card-body text-center">
                    <img src="<?= $channel['avatar'] ?>" class="rounded-circle center-block" width="72px"
                         alt="Channel"/>
                    <h4 class="card-title"><?= $channel['name'] ?></h4>
                    <p class="card-text"><?= htmlspecialchars($channel['description']) ?></p>
                </div>
            </div>
            <?php foreach ($articles as $article): ?>
                <div class="card neo_card mt-2">
                    <div class="card-body">
                        <div class="media">
                            <img class="rounded-circle"
                                 src="<?= $channel['avatar'] ?>"
                                 width="48px"/>
                            <div class="media-body ml-3">
                                <div>
								<span class="username">
									<a href="#" class="text-muted font-weight-bold"><?= $channel['name'] ?></a>
								</span>
                                </div>
                                <div class="small">
                                    <span class="text-grey"><?= date('Y-m-d H:i:s', $article['timestamp']) ?></span>
                                </div>
                            </div>
                        </div>
                        <p class="mt-2 card-text"><?= $article['summary'] ?></p>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
        <div class="col-lg-3">

        </div>
    </div>
</div>
<?php include "template/common/footer.html"; ?>
</body>
</html>