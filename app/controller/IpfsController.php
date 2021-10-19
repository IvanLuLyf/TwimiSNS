<?php


use BunnyPHP\BunnyPHP;
use BunnyPHP\Controller;

class IpfsController extends Controller
{
    /**
     * @param array $hash path()
     */
    public function other(array $hash = [])
    {
        $extra = $hash ? ('/' . implode('/', $hash)) : '';
        $path = BUNNY_ACTION . $extra;
        $tag = md5($path);
        $last_modified_time = BunnyPHP::getCache()->get('ipfs://' . $path);
        if (!$last_modified_time) {
            $last_modified_time = time();
            BunnyPHP::getCache()->set('ipfs://' . $path, $last_modified_time);
        }
        $gmt_mtime = gmdate('r', $last_modified_time);
        header('ETag: "' . $tag . '"');
        header('Last-Modified: ' . $gmt_mtime);
        header('Cache-Control: public');
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $gmt_mtime || str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $tag) {
                header('HTTP/1.1 304 Not Modified');
                exit();
            }
        }
        header('Content-type: image');
        echo (new IpfsStorage())->read($path);
    }
}