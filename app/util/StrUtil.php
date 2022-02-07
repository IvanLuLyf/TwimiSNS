<?php


class StrUtil
{
    public static function emptyText($text): bool
    {
        return strlen(trim($text)) === 0;
    }

    public static function sqlIn($data = []): string
    {
        return implode(',', array_pad([], count($data), '?'));
    }
}