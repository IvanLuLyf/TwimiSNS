<?php


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
        var_dump($path);
    }
}