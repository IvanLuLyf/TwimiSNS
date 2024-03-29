<html lang="zh">
<head>
    <title>头像 - <?= constant('TP_SITE_NAME') ?></title>
    <?php include APP_PATH . 'template/common/header.php'; ?>
    <link href="https://cdn.bootcss.com/croppie/2.6.3/croppie.min.css" rel="stylesheet">
    <script src="https://cdn.bootcss.com/croppie/2.6.3/croppie.min.js"></script>
</head>
<body>
<?php include APP_PATH . 'template/common/navbar.php'; ?>
<div class="modal fade" id="loadingModal" tabindex="-1" role="dialog" aria-labelledby="loadingModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loadingModalLabel">上传成功</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                头像上传成功
            </div>
            <div class="modal-footer">
                <a href="/setting/avatar" role="button" class="btn btn-dark">确定</a>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <div class="col-lg-3">
            <?php include APP_PATH . 'template/setting/nav.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="card neo_card mt-4">
                <div class="card-body row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label>当前头像</label>
                            <img id="img_avatar" height="100px" width="100px" style="display: block;"
                                 class="rounded-circle" src="/user/avatar/<?= $tp_user['uid'] ?>" alt="avatar">
                        </div>
                        <div id="avatar-container">
                            <div id="avatar_crop" class="" style="width:100%;display: none;max-height: 400px;">
                            </div>
                            <button type="button" id="btn_confirm" onclick="confirmClick()"
                                    class="btn btn-dark btn-block"
                                    style="margin-top: 50px;display: none;">确认裁剪
                            </button>
                        </div>
                        <input type="file" style="height:0;width:0;display: none;" id="avatar" name="avatar"
                               placeholder="头像"
                               required/>
                        <div class="form-group">
                            <button id="btn_sel" class="btn btn-dark btn-block" onclick="selectFile()">选择图片</button>
                        </div>
                        <div class="form-group">
                            <button id="btn_upload" class="btn btn-dark btn-block" onclick="uploadClick()" type="button"
                                    style="display: none;">
                                上传头像
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include APP_PATH . 'template/common/footer.php'; ?>
<script>
    let cropper = null;
    let img_blob = null;

    function selectFile() {
        $("#avatar").click();
    }

    $("#avatar").on("change", function () {
        let reader = new FileReader();
        let avatar_crop = $('#avatar_crop');
        reader.onload = function (evt) {
            avatar_crop.show();
            $("#btn_confirm").show();
            $('#btn_sel').hide();
            cropper = avatar_crop.croppie({
                url: evt.target.result, viewport: {
                    width: 200,
                    height: 200
                }
            });
        };
        reader.readAsDataURL(document.getElementById("avatar").files[0]);
    });

    function confirmClick() {
        cropper.croppie('result', 'blob').then(function (blob) {
            let img = document.getElementById("img_avatar");
            img_blob = blob;
            img.onload = function (e) {
                window.URL.revokeObjectURL(img.src);
                $('#avatar_crop').hide();
                $("#btn_confirm").hide();
                $('#btn_upload').show();
            };
            img.src = window.URL.createObjectURL(blob);
        });
    }

    function uploadClick() {
        let formData = new FormData();
        formData.append("avatar", img_blob);
        $.ajax({
            url: "/ajax/user/avatar",
            type: 'post',
            processData: false,
            contentType: false,
            data: formData,
            success: function (data) {
                console.log(data);
                $('#loadingModal').modal('show');
            }
        });
    }
</script>
</body>
</html>