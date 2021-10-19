<?php

use BunnyPHP\Cache;

class TmpCache implements Cache
{
    protected string $dir;
    protected string $cacheDir;

    public function __construct($config)
    {
        $this->dir = $config['dir'] ?? '@cache';
        if ($this->dir[0] === '@') {
            $this->cacheDir = APP_PATH . $this->dir . '/';
        } else {
            $this->cacheDir = $this->dir . '/';
        }
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function get(string $key, int $expire = 0)
    {
        $filename = $this->cacheDir . sha1($key);
        if (file_exists($filename)) {
            if ((filemtime($filename) + $expire > time()) || $expire === 0) {
                return file_get_contents($this->cacheDir . sha1($key));
            } else {
                unlink($filename);
                return null;
            }
        } else {
            return null;
        }
    }

    public function has(string $key, int $expire = 0): bool
    {
        $filename = $this->cacheDir . sha1($key);
        return file_exists($filename) && ((filemtime($filename) + $expire > time()) || $expire === 0);
    }

    public function set(string $key, $value, int $expire = 0)
    {
        file_put_contents($this->cacheDir . sha1($key), $value);
    }

    public function del(string $key)
    {
        $filename = $this->cacheDir . sha1($key);
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
}