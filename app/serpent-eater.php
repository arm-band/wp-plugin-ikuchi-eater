<?php

namespace IkuchiEater\app;

require_once( ABSPATH . '/wp-admin/includes/template.php' );

/**
 * 蛇食らい
 *
 * desc: 設定画面用ウォーカー
 *
 */
class SerpentEater extends \Walker_Category_Checklist
{
    /**
     * var
     *
     */
    protected $c;
    protected $taxonomies_array;
    /**
     * コンストラクタ
     *
     */
    function __construct( $c, $taxonomies_array )
    {
        $this->c               = $c;
        $this->taxonomies_array = $taxonomies_array;
    }

    /**
     * Start the element output.
     *
     * @see Walker::start_el()
     *
     * @since 2.5.1
     *
     * @param string $output   Used to append additional content (passed by reference).
     * @param object $category The current term object.
     * @param int    $depth    Depth of the term in reference to parents. Default 0.
     * @param array  $args     An array of arguments. @see wp_terms_checklist()
     * @param int    $id       ID of the current term.
     */
    function start_el(
        &$output,
        $category,
        $depth = 0,
        $args = Array(),
        $id = 0
    )
    {
        extract( $args );
        if( empty( $taxonomy )) {
            $taxonomy = 'category';
        }

        if( $taxonomy == 'category' ) {
            $name = 'post_category';
        }
        else {
            $name = $taxonomy;
        }

        $class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';

        $i = function ($v) { return $v; };

        $id = esc_attr( $name ) . '---' . esc_attr( $category->term_id );

        $checked = $this->taxonomies_array[$id] === 1 ? ' checked="checked"' : '';

        $output .= "\n<li id=\"{$taxonomy}-{$category->term_id}\"{$class}><input type=\"checkbox\" id=\"{$id}\" name=\"{$i( esc_attr( $this->c['IKUCHIEATER_TAXONOMIES_CHECKBOXES'] ))}[{$id}]\" {$checked} value=\"1\"><label for=\"{$id}\">{$i( esc_attr( apply_filters('the_category', $category->name )))}</label>";
    }
}
