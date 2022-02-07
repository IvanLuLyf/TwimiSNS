<html lang="zh">
<head>
    <title><?= constant('TP_SITE_NAME') ?></title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
    <style>
        .message {
            margin: 0.75rem;
            padding: 0.75rem;
            border-radius: 0.25rem;
            background: mistyrose;
        }

        .message table {
            border-collapse: collapse;
            width: 100%;
        }

        .message table td, .message table th {
            border: 1px solid lightyellow;
            color: #666;
            height: 30px;
        }

        .message table tr:nth-child(odd) {
            background: white;
        }

        .message table tr:nth-child(even) {
            background: floralwhite;
        }
    </style>
</head>
<body>
<?php include APP_PATH . 'template/common/navbar.php'; ?>
<div class="container">
    <div class="row">
        <div class="col-lg-9">
            <div class="card neo_card mt-4">
                <div class="card-body">
                    <h1 class="card-title"><?= $tp_error_msg ?? $bunny_error ?? '' ?></h1>
                    <?php if (APP_DEBUG): ?>
                        <?php if (isset($bunny_error_trace)): ?>
                            <h4 class="sub-title">Trace</h4>
                            <div class="message">
                                <table>
                                    <tbody>
                                    <tr class="bg2">
                                        <td>No.</td>
                                        <td>File</td>
                                        <td>Line</td>
                                        <td>Code</td>
                                    </tr>
                                    <?php foreach ($bunny_error_trace as $i => $t): ?>
                                        <tr class="bg1">
                                            <td><?= ($i + 1) ?></td>
                                            <td><?= $t['file'] ?? '-' ?></td>
                                            <td><?= $t['line'] ?? '-' ?></td>
                                            <td><?= $t['class'] ?? '' ?><?= $t['type'] ?? '' ?><?= $t['function'] ?? '' ?>
                                                ()
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a class="btn btn-dark text-white mt-5" href="/" role="button">返回首页</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include APP_PATH . 'template/common/footer.php'; ?>
</body>
</html>