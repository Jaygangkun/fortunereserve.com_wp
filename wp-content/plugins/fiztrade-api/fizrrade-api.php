<?php

/**
 * Plugin Name: Fiz Trade API
 * Plugin URI: https://woocommerce.com/
 * Description: An eCommerce toolkit that helps you sell anything. Beautifully.
 * Version: 6.7.0
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 * Requires at least: 5.8
 * Requires PHP: 7.2
 *
 * @package WooCommerce
 */


define('FIZ_TRADE_DIR', plugin_dir_path(__FILE__));
define('FIZ_TRADE_URL', plugin_dir_url(__FILE__));
define('FIZ_TRADE_SCRIPTS_URL', plugin_dir_url(__FILE__) . 'scripts/');

if( !class_exists('Fiz_Trade_Settings') ){
    require_once FIZ_TRADE_DIR . 'classes/class_fiz_trade_settings.php';
    $settings = new Fiz_Trade_Settings();
}

if( !class_exists('Fiz_Trade_Api') ){
    require_once FIZ_TRADE_DIR . 'classes/class_fiz_trade_api.php';
    $api = new Fiz_Trade_Api();
}

if( !class_exists('Fiz_Trade_Ajax') ){
    require_once FIZ_TRADE_DIR . 'classes/class_fiz_trade_ajax.php';
    $ajax = new Fiz_Trade_Ajax($api);
}

if( !class_exists('Fiz_Trade_Cron') ){
    require_once FIZ_TRADE_DIR . 'classes/class_fiz_trade_cron.php';
    $cron = new Fiz_Trade_Cron($api);
}

