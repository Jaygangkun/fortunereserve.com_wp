<?php

class Fiz_Trade_Settings
{

    public function __construct() {
        add_action('acf/init', [$this, 'acf_init'] );
        add_action( 'admin_enqueue_scripts', [$this, 'admin_script'] );
    }

    public function acf_init() {
        if( function_exists('acf_add_options_page') ) {

            // Register options page.
            $option_page = acf_add_options_page(array(
                'page_title'    => __('FizTradeApi Settings'),
                'menu_title'    => __('FizTradeApi Settings'),
                'menu_slug'     => 'FizTradeApi-settings',
                'capability'    => 'edit_posts',
                'redirect'      => false
            ));
        }
    }

    public function admin_script() {
        wp_enqueue_script( 'admin_script', FIZ_TRADE_SCRIPTS_URL . 'admin-script.js', array(), '1.0' );
    }
}