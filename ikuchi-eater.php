<?php
/*
Plugin Name: Ikuchi Eater
Description: 通常の投稿のみ、特定のカテゴリーでスラッグ決定時に機械的にスラッグを上書きする。スラッグのフォーマットは <post_type>-<post_id>-yyyy-mm-dd
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
     * var
     *
     */
    protected $c;
    protected $instance;
    protected $Init;
    /**
     * コンストラクタ
     *
     */
    public function __construct()
    {
        try {
            if( !require_once(__DIR__ . '/app/init.php') ) {
                throw new \Exception( '初期化ファイル読み込みに失敗しました: init.php' );
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        $this->Init = new \IkuchiEater\app\Init();
        $this->c = $this->Init->getConstant();
        $this->instance = $this->Init->getInstance();
    }

    /**
     * 管理者画面にメニューと設定画面を追加、プラグインの機能有効化
     *
     */
    public function initialize()
    {
        // メニューを追加
        add_action( 'admin_menu', [ $this, 'ikuchieater_create_menu' ] );
        // 独自関数をコールバック関数とする
        add_action( 'admin_init', [ $this, 'register_ikuchieater_settings' ] );

        // プラグインの機能有効化: css
        add_action( 'admin_print_styles', [ $this, 'add_css' ] );
        // プラグインの機能有効化
        add_action(
            'publish_post',
            [
                $this,
                'mushroom'
            ],
            10,
            1
        );
        add_action(
            'post_updated',
            [
                $this,
                'toadstool'
            ],
            10,
            3
        );
    }
    /**
     * メニュー追加
     *
     */
    public function ikuchieater_create_menu()
    {
        // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
        //  $page_title : 設定ページの `title` 部分
        //  $menu_title : メニュー名
        //  $capability : 権限 ( 'manage_options' や 'administrator' など)
        //  $menu_slug  : メニューのslug
        //  $function   : 設定ページの出力を行う関数
        //  $icon_url   : メニューに表示するアイコン
        //  $position   : メニューの位置 ( 1 や 99 など )
        add_menu_page(
            $this->c['IKUCHIEATER_SETTINGS'],
            $this->c['IKUCHIEATER_SETTINGS'],
            'administrator',
            $this->c['IKUCHIEATER'],
            [
                $this,
                $this->c['IKUCHIEATER'] . '_settings_page'
            ],
            'dashicons-art'
        );
    }
    /**
     * コールバック
     *
     */
    public function register_ikuchieater_settings()
    {
        // register_setting( $option_group, $option_name, $sanitize_callback )
        //  $option_group      : 設定のグループ名
        //  $option_name       : 設定項目名(DBに保存する名前)
        //  $sanitize_callback : 入力値調整をする際に呼ばれる関数
        register_setting(
            $this->c['IKUCHIEATER_SETTINGS_EN'],
            $this->c['IKUCHIEATER_TAXONOMIES_CHECKBOXES'],
            [
                $this,
                $this->c['IKUCHIEATER_CHECKBOXES_VALIDATION']
            ]
        );
    }
    /**
     * チェックボックスのバリデーション。コールバックから呼ばれる
     *
     * @param array $new_input 設定画面で入力されたパラメータ
     *
     * @return string $new_input / $ANONYMOUS バリデーションに成功した場合は $new_input そのものを返す。失敗した場合はDBに保存してあった元のデータを get_option で呼び戻す。
     *
     */
    public function ikuchieater_checkboxes_validation( $new_input )
    {
        // nonce check
        check_admin_referer( $this->c['IKUCHIEATER'] . '_options', 'name_of_nonce_field' );

        // validation
        $err_cnt = 0;
        foreach( $new_input as $key => $value ) {
            if( preg_match('/^[\d]{1}$/i', $value) ) {
                $new_input[$key] = (int) $value;
                if ( $new_input[$key] !== 0 && $new_input[$key] !== 1 ) {
                    $err_cnt++;
                }
            }
            else {
                $err_cnt++;
            }
        }
        if( $err_cnt > 0 ) {
            // add_settings_error( $setting, $code, $message, $type )
            //  $setting : 設定のslug
            //  $code    : エラーコードのslug (HTMLで'setting-error-{$code}'のような形でidが設定されます)
            //  $message : エラーメッセージの内容
            //  $type    : メッセージのタイプ。'updated' (成功) か 'error' (エラー) のどちらか
            add_settings_error(
                $this->c['IKUCHIEATER'],
                $this->c['IKUCHIEATER'] . '_checkboxes-validation_error',
                __(
                    '選択したタクソノミーの一覧に不正なデータが含まれています。',
                    $this->c['IKUCHIEATER']
                ),
                'error'
            );

            return get_option( $this->c['IKUCHIEATER'] . '_taxonomies_checkboxes' ) ? get_option( $this->c['IKUCHIEATER'] . '_taxonomies_checkboxes' ) : [];
        }
        else {
            return $new_input;
        }
    }
    /**
     * プラグインの機能有効化: css / トップレベルページ
     *
     */
    public function add_css()
    {
        global $hook_suffix;
        if( 'toplevel_page_' . $this->c['IKUCHIEATER'] === $hook_suffix ) {
            wp_enqueue_style( 'ikuchi_css', plugins_url( '', __FILE__ ) . '/css/ikuchi.css' );
        }
    }
    /**
     * 設定画面ページの生成
     *
     */
    public function ikuchieater_settings_page()
    {
        if( get_settings_errors( $this->c['IKUCHIEATER'] ) ) {
            // エラーがあった場合はエラーを表示
            settings_errors( $this->c['IKUCHIEATER'] );
        }
        else if( true == $_GET['settings-updated'] ) {
            //設定変更時にメッセージ表示
?>
            <div id="settings_updated" class="updated notice is-dismissible"><p><strong>設定を保存しました。</strong></p></div>
<?php
        }
?>

        <div class="wrap">
            <h1><?= esc_html( $this->c['IKUCHIEATER_SETTINGS'] ); ?></h1>
            <h2>スラッグを整形する処理を実施するカテゴリー</h2>
            <p>以下のチェックリストから、スラッグを整形する処理を実施するカテゴリーにチェックを入れてください。</p>
            <form method="post" action="options.php">
<?php settings_fields( $this->c['IKUCHIEATER_SETTINGS_EN'] ); ?>
<?php do_settings_sections( $this->c['IKUCHIEATER_SETTINGS_EN'] ); ?>
                <table class="form-table" id="<?= esc_attr( $this->c['IKUCHIEATER_TAXONOMIES_CHECKBOXES'] ); ?>-table">
<?php
        $terms_objs = get_object_taxonomies( 'post', 'objects' );
        foreach ( $terms_objs as $terms_obj ) {
            if( $terms_obj->name === 'category' ) {
?>
                    <tr>
                        <th><?= esc_html( $postType->label ); ?>: <?= esc_html( $terms_obj->label ); ?></th>
                        <td>
                            <ul>
<?php
                $serpent_eater = $this->instance['SerpentEater'];
                wp_terms_checklist( 0, [
                    'walker'        => $serpent_eater,
                    'taxonomy'      => $terms_obj->name,
                    'checked_ontop' => false,
                ] );
?>
                            </ul>
                        </td>
                    </tr>
<?php
            }
        }
?>
                </table>
<?php wp_nonce_field( $this->c['IKUCHIEATER'] . '_options', 'name_of_nonce_field' ); ?>
<?php submit_button( '設定を保存', 'primary large', 'submit', true, [ 'tabindex' => '1' ] ); ?>
            </form>
        </div>

<?php
    }
    /**
     * mushroom
     *
     * description                : 記事公開時
     *
     * @param int    $post_id     : 投稿ID
     *
     */
    public function mushroom(
        $post_id
    )
    {
        self::mucus( $post_id, get_post($post_id) );
    }
    /**
     * toadstool
     *
     * description                : 記事更新時
     *
     * @param int    $post_id     : 投稿ID
     * @param object $post_after  : 更新後の投稿
     * @param object $post_before : 更新前の投稿
     *
     */
    public function toadstool(
        $post_id,
        $post_after,
        $post_before
    )
    {
        self::mucus( $post_id, $post_after );
    }
    /**
     * mucus
     *
     * description                : 通常の投稿、設定画面で指定したカテゴリーを含むときのみ、の条件判定
     *
     * @param int    $post_id     : 投稿ID
     * @param object $post        : 投稿データ
     *
     * @return boolean
     *
     */
    public function mucus(
        $post_id,
        $post
    )
    {
        if( $post->post_type === 'post' ) {
            $checked_categories = $this->Init->dataRead();
            foreach ($checked_categories as $key => $val) {
                if(
                    in_array(
                        (int)explode( '---', $key )[1],
                        array_column(
                            get_the_terms(
                                $post_id,
                                'category' // 投稿のカテゴリー
                            ),
                            'term_id'
                        ),
                        true
                    )
                ) {
                    // スラッグ書き換え
                    add_filter(
                        'wp_unique_post_slug',
                        [
                            $this,
                            'viscousLiquid'
                        ],
                        10,
                        4
                    );
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * viscousLiquid
     *
     * description                : 通常の投稿のみ、スラッグ決定時に機械的にスラッグを上書きする。スラッグのフォーマットは <post_type>-<post_id>-yyyy-mm-dd (month, date は0詰めあり)
     *
     * @param string $slug        : オリジナルのスラッグ
     * @param int $post_id        : 投稿ID
     * @param string $post_status : 投稿の状態
     * @param string $post_type   : 投稿タイプ
     *
     * @return string $slug       : スラッグ
     */
    public function viscousLiquid(
        $slug,
        $post_id,
        $post_status,
        $post_type
    )
    {
        $slug = utf8_uri_encode( $post_type ) . '-' . $post_id . '-' . date('Y-m-d');
        ob_start();
        var_dump($slug);
        $result = ob_get_clean();
        $log_message = sprintf("%s:%s\n", date_i18n('Y-m-d H:i:s'), htmlspecialchars($result, ENT_QUOTES, 'UTF-8'));
        error_log($log_message, 3, __DIR__ . '/debug_log.txt');
        return $slug;
    }
}

$wp_ab_ikuchieater = new IkuchiEater();

if( is_admin() ) {
    // 管理者画面を表示している場合のみ実行
    $wp_ab_ikuchieater->initialize();
}
