<?php


use BunnyPHP\BunnyPHP;
use BunnyPHP\Controller;

class IpfsController extends Controller
{
    /**
     * @param string $storageName config(storage.name)
     * @param array $hash path()
     */
    public function other(string $storageName, array $hash = [])
    {
        $extra = $hash ? ('/' . implode('/', $hash)) : '';
        $path = BUNNY_ACTION . $extra;
        if ($storageName == 'ipfs') {
            header('Cache-Control: public');
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
                header('HTTP/1.1 304 Not Modified');
                exit();
            }
            header('ETag: "' . md5($path) . '"');
            header('Last-Modified: ' . gmdate('r', time()));
            header('Content-type: image');
            echo (BunnyPHP::getStorage())->read($path);
        } else {
            $this->redirect("https://ipfs.io/ipfs/$path");
        }
    }
}