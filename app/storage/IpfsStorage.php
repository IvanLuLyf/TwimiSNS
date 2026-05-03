<?php

use BunnyPHP\Storage;

/**
 * @author IvanLu
 * @time 2019/2/27 3:37
 */
class IpfsStorage implements Storage
{
    private $server;

    /** @var string Public gateway base URL, no trailing slash */
    private $gateway;

    public function __construct($config = [])
    {
        $this->server = ($config['server'] ?? '') ?: 'https://ipfs.infura.io:5001/api/v0';
        $this->gateway = rtrim((string)($config['gateway'] ?? 'https://ipfs.io'), '/');
    }

    public function read($filename)
    {
        return RequestUtil::doPost($this->server . "/cat?arg=" . $filename);
    }

    public function write($filename, $content): string
    {
        $data = json_decode(RequestUtil::doUpload($this->server . '/add', $filename, $content), true);
        return '/ipfs/' . $data['Hash'];
    }

    public function upload(string $filename, string $path): string
    {
        $data = json_decode(RequestUtil::doUpload($this->server . '/add', $path), true);
        return '/ipfs/' . $data['Hash'];
    }

    public function remove($filename)
    {

    }

    public function toPublicUrl(string $reference): string
    {
        if ($reference === '') {
            return $this->gateway;
        }
        if (strpos($reference, 'ipfs://') === 0) {
            return $this->gateway . '/ipfs/' . ltrim(substr($reference, 7), '/');
        }
        if (strpos($reference, '/ipfs/') === 0) {
            return $this->gateway . $reference;
        }
        return $this->gateway . '/ipfs/' . ltrim($reference, '/');
    }
}