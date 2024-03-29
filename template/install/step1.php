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
            <h2>数据库配置</h2>
            <form method="post" action="/install/step2">
                <div class="form-group">
                    <label for="db_type">数据库类型</label>
                    <select name="db_type" class="form-control" id="db_type" onchange="selectDB(this.value)">
                        <option value="mysql">MySQL</option>
                        <option value="pgsql">PostgreSQL</option>
                        <option value="sqlite">SQLite</option>
                    </select>
                </div>
                <div class="form-group" id="div_host">
                    <label for="db_host">数据库服务器</label>
                    <input type="text" name="db_host" class="form-control" id="db_host" placeholder="数据库服务器">
                </div>
                <div class="form-group" id="div_port">
                    <label for="db_port">数据库端口号</label>
                    <input type="text" name="db_port" class="form-control" id="db_port" placeholder="数据库端口号">
                </div>
                <div class="form-group" id="div_user">
                    <label for="db_user">数据库用户名</label>
                    <input type="text" name="db_user" class="form-control" id="db_user" placeholder="数据库用户名">
                </div>
                <div class="form-group" id="div_pass">
                    <label for="db_pass">数据库密码</label>
                    <input type="password" name="db_pass" class="form-control" id="db_pass" placeholder="数据库密码">
                </div>
                <div class="form-group">
                    <label for="db_name">数据库名</label>
                    <input type="text" name="db_name" class="form-control" id="db_name" placeholder="数据库名" required>
                </div>
                <div class="form-group">
                    <label for="db_prefix">数据表前缀</label>
                    <input type="text" name="db_prefix" class="form-control" id="db_prefix" value="tp_">
                </div>
                <input type="submit" class="btn btn-dark" value="下一步">
            </form>
        </div>
    </div>
</div>
<script>
    function selectDB(db) {
        if (db === 'mysql') {
            $('#div_host').show();
            $('#div_port').show();
            $('#div_user').show();
            $('#div_pass').show();
        } else if (db === 'pgsql') {
            $('#div_host').show();
            $('#div_port').show();
            $('#div_user').show();
            $('#div_pass').show();
        } else if (db === 'sqlite') {
            $('#div_host').hide();
            $('#div_port').hide();
            $('#div_user').hide();
            $('#div_pass').hide();
            $('#db_name').val('sns.sqlite3');
        }
    }
</script>
</body>
</html>