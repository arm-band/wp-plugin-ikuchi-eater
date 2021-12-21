<?php
/*
Plugin Name: Ikuchi Eater
Description: 通常の投稿のみ、スラッグ決定時に機械的にスラッグを上書きする。スラッグのフォーマットは {post_type}-{post_id}-yyyy-mm-dd
Version:     0.0.2
Author:      アルム＝バンド
*/

namespace IkuchiEater;

date_default_timezone_set('Asia/Tokyo');
mb_language('ja');
mb_internal_encoding('UTF-8');

class IkuchiEater
{
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        add_filter(
            'wp_unique_post_slug',
            [
                $this,
                'mucus'
            ],
            10,
            4
        );
    }

    /**
     * mucus
     *
     * description                : 通常の投稿のみ、スラッグ決定時に機械的にスラッグを上書きする。スラッグのフォーマットは <post_type>-<post_id>-yyyy-mm-dd (month, date は0詰めあり)
     *
     * @param stinrg $slug        : オリジナルのスラッグ
     * @param int    $post_ID     : 投稿ID
     * @param string $post_status : 投稿の状況
     * @param string $post_type   : 投稿タイプ
     *
     * @return string $slug       : 加工したスラッグ
     */
    public function mucus(
        $slug,
        $post_ID,
        $post_status,
        $post_type
    )
    {
        if (
            $post_type === 'post'
            && !preg_match( '/^[\w\-_]+\-[\d]+\-(20|19)\d{2}\-\d{2}\-\d{2}$/i', $slug )
        ) {
            $slug = utf8_uri_encode( $post_type ) . '-' . $post_ID . '-' . date('Y-m-d');
        }
        return $slug;
    }
}

if( is_admin() ) {
    // 管理者画面を表示している場合のみ実行
    $wp_ab_ikuchieater = new IkuchiEater();
}
