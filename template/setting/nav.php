<div class="card neo_card mt-4">
    <div class="card-body">
        <div class="nav flex-column nav-pills" role="tablist" aria-orientation="vertical">
            <a class="nav-link link-dark <?= (isset($cur_st) && $cur_st == 'avatar') ? 'active' : '' ?>"
               href="/setting/avatar">头像</a>
            <a class="nav-link link-dark <?= (isset($cur_st) && $cur_st == 'oauth') ? 'active' : '' ?>"
               href="/setting/oauth">账号绑定</a>
            <a class="nav-link link-dark" href="#">#</a>
        </div>
    </div>
</div>