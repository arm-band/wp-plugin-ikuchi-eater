<?php

namespace IkuchiEater\app;

/**
 * init
 *
 * description : 初期化・準備
 *
 */
class Init
{
    /**
     * const
     *
     */
    const IKUCHIEATER                       = 'ikuchieater';
    const IKUCHIEATER_SETTINGS              = 'イクチイーター 設定';
    const IKUCHIEATER_SETTINGS_EN           = self::IKUCHIEATER . '-settings';
    const IKUCHIEATER_TAXONOMIES_CHECKBOXES = self::IKUCHIEATER . '_taxonomies_checkboxes';
    const IKUCHIEATER_CHECKBOXES_VALIDATION = self::IKUCHIEATER . '_checkboxes_validation';
    const ENCODING                          = 'UTF-8';
    /**
     * var
     *
     */
    protected $c;
    /**
     * コンストラクタ
     *
     */
    public function __construct()
    {
        $this->c = [
            'IKUCHIEATER'                       => self::IKUCHIEATER,
            'IKUCHIEATER_SETTINGS'              => self::IKUCHIEATER_SETTINGS,
            'IKUCHIEATER_SETTINGS_EN'           => self::IKUCHIEATER_SETTINGS_EN,
            'IKUCHIEATER_TAXONOMIES_CHECKBOXES' => self::IKUCHIEATER_TAXONOMIES_CHECKBOXES,
            'IKUCHIEATER_CHECKBOXES_VALIDATION' => self::IKUCHIEATER_CHECKBOXES_VALIDATION,
            'ENCODING'                          => self::ENCODING,
        ];
    }
    /**
     * 定数返し
     *
     * @return array $c クラス内で宣言した定数を出力する
     *
     */
    public function getConstant()
    {
        return $this->c;
    }
    /**
     * htmlspecialchars のラッパー関数
     *
     * esc_html ではクォートもエスケープされてしまうため、JS処理時は不都合がある
     *
     * @param string $str 文字列
     *
     * @return string $ANONYMOUS $str を エスケープして返す(クォートを除く)
     *
     */
    public function _h( $str )
    {
        return htmlspecialchars( $str, ENT_NOQUOTES, self::ENCODING );
    }
    /**
     * データ読み込み
     *
     * @return array $ANONYMOUS DB から
     *
     */
    public function dataRead()
    {
        return maybe_unserialize( get_option( self::IKUCHIEATER_TAXONOMIES_CHECKBOXES ) );
    }
    /**
     * インスタンス返し
     *
     * @return array $instance コンストラクタで宣言した文字列の名前のファイルを探し、require_once して new してインスタンスを返す
     *
     */
    public function getInstance()
    {
        $instance = [];
        try {
            $c = self::getConstant();
            $taxonomies_array = self::dataRead();
            if( require_once( __DIR__ . '/serpent-eater.php' ) ) {
                $instance['SerpentEater'] = new \IkuchiEater\app\SerpentEater( $c, $taxonomies_array );
            }
            else {
                throw new \Exception( 'クラスファイル読み込みに失敗しました: serpent-eater.php' );
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $instance;
    }
}
