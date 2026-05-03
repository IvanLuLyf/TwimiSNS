<?php

class PostsUtil
{
    /**
     * @param array $post
     * @param array|null $tp_user
     * @return int show_state 0=full 1=paid gate 2=login gate
     */
    public static function apply(array &$post, ?array $tp_user): int
    {
        $showState = 0;
        $post['show_state'] = 0;
        if (($post['extra'] ?? '') === '' || $post['extra'] === null) {
            return 0;
        }
        $extra = json_decode($post['extra'], true);
        if (!is_array($extra)) {
            return 0;
        }
        if (($extra['type'] ?? '') === 'paid'
            && (
                $tp_user === null
                || ($post['username'] !== $tp_user['username'] && !(new PostPayModel())->check($tp_user['uid'], (int)$post['tid']))
            )) {
            $post['content'] = '[付费帖子]';
            $post['coin'] = $extra['price'] ?? 0;
            $showState = 1;
        } elseif (($extra['type'] ?? '') === 'login' && $tp_user === null) {
            $post['content'] = '[登录可见]';
            $showState = 2;
        }
        $post['show_state'] = $showState;

        return $showState;
    }
}
