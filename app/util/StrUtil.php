<?php


class StrUtil
{
    public static function emptyText($text): bool
    {
        return strlen(trim($text)) === 0;
    }
}