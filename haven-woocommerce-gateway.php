<?php
/*
Plugin Name: Haven Woocommerce Gateway
Plugin URI: https://github.com/haven-protocol-org/xUSD-wp
Description: Extends WooCommerce by adding a Haven Gateway
Version: 0.0.1
Tested up to: 4.9.8
Author:zrero, mosu-forge, SerHack
Author URI: https://havenprotocol.org/
*/
// This code isn't for Dark Net Markets, please report them to Authority!

defined( 'ABSPATH' ) || exit;

// Constants, you can edit these if you fork this repo
define('HAVEN_GATEWAY_MAINNET_EXPLORER_URL', 'https://explorer.havenprotocol.org/');
define('HAVEN_GATEWAY_TESTNET_EXPLORER_URL', 'https://explorer.testnet.havenprotocol.org/');
define('HAVEN_GATEWAY_MAINNET_ADDRESS_PREFIX', 0x5af4);
define('HAVEN_GATEWAY_MAINNET_ADDRESS_PREFIX_INTEGRATED', 0xcd774);
define('HAVEN_GATEWAY_TESTNET_ADDRESS_PREFIX', 0x59f4);
define('HAVEN_GATEWAY_TESTNET_ADDRESS_PREFIX_INTEGRATED', 0x499f4);
define('HAVEN_GATEWAY_ATOMIC_UNITS', 12);
define('HAVEN_GATEWAY_ATOMIC_UNIT_THRESHOLD', 10); // Amount under in atomic units payment is valid
define('HAVEN_GATEWAY_DIFFICULTY_TARGET', 120);

define('HAVEN_XASSETS',
[
  'xUSD' =>
  [
    'symbol' => '$',
    'wc'     => 'USD'
  ],
  'xEUR' =>
  [
    'symbol' => '€',
    'wc'     => 'EUR'
  ],
  'xCNY' =>
  [
    'symbol' => '¥',
    'wc'     => 'CNY'
  ],
  'xGOLD' =>
  [
    'symbol' => 'G',
    'wc'     => 'GOLD'
  ]
]
);

// Do not edit these constants
define('HAVEN_GATEWAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HAVEN_GATEWAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HAVEN_GATEWAY_ATOMIC_UNITS_POW', pow(10, HAVEN_GATEWAY_ATOMIC_UNITS));
define('HAVEN_GATEWAY_ATOMIC_UNITS_SPRINTF', '%.'.HAVEN_GATEWAY_ATOMIC_UNITS.'f');

$xAssetSelected = "";

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action('plugins_loaded', 'haven_init', 1);
function haven_init() {

    // If the class doesn't exist (== WooCommerce isn't installed), return NULL
    if (!class_exists('WC_Payment_Gateway')) return;

    // If we made it this far, then include our Gateway Class
    require_once('include/class-haven-gateway.php');

    // Create a new instance of the gateway so we have static variables set up
    new Haven_Gateway($add_action=false);

    // Include our Admin interface class
    require_once('include/admin/class-haven-admin-interface.php');

    add_filter('woocommerce_payment_gateways', 'haven_gateway');
    function haven_gateway($methods) {
        $methods[] = 'Haven_Gateway';
        return $methods;
    }


  add_filter( 'woocommerce_available_payment_gateways', 'haven_unset_gateway_if_unused' );

  function haven_unset_gateway_if_unused( $available_gateways ) {
      global $xAssetSelected;
      if ( is_admin() ) return $available_gateways;
      if ( ! is_checkout() ) return $available_gateways;
      $unset = true;
      $selected_currency = get_woocommerce_currency();

      foreach(HAVEN_XASSETS as $xAsset => $options){
        //Ony be able to activate when xAsset = xAsset OR if xAsset = (x)Asset
        if($xAsset == $selected_currency || $options['wc'] == $selected_currency){
          $xAssetSelected = $xAsset;
          $unset = false;
          break;
        }
      }

      if($unset){
        unset( $available_gateways['haven_gateway'] );
      }
      return $available_gateways;
  }

  add_filter( 'woocommerce_gateway_title', 'change_cheque_payment_gateway_title', 100, 2 );
  function change_cheque_payment_gateway_title( $title, $payment_id ){
      global $xAssetSelected;
      if( $payment_id === 'haven_gateway' ) {
          $title = __($title." ($".$xAssetSelected.")", "woocommerce");
      }
      return $title;
  }

    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'haven_payment');
    function haven_payment($links) {
        $plugin_links = array(
            '<a href="'.admin_url('admin.php?page=haven_gateway_settings').'">'.__('Settings', 'haven_gateway').'</a>'
        );
        return array_merge($plugin_links, $links);
    }

    add_filter('cron_schedules', 'haven_cron_add_one_minute');
    function haven_cron_add_one_minute($schedules) {
        $schedules['one_minute'] = array(
            'interval' => 60,
            'display' => __('Once every minute', 'haven_gateway')
        );
        return $schedules;
    }

    add_action('wp', 'haven_activate_cron');
    function haven_activate_cron() {
        if(!wp_next_scheduled('haven_update_event')) {
            wp_schedule_event(time(), 'one_minute', 'haven_update_event');
        }
    }

    add_action('haven_update_event', 'haven_update_event');
    function haven_update_event() {
        Haven_Gateway::do_update_event();
    }

    add_action('woocommerce_thankyou_'.Haven_Gateway::get_id(), 'haven_order_confirm_page');
    add_action('woocommerce_order_details_after_order_table', 'haven_order_page');
    add_action('woocommerce_email_after_order_table', 'haven_order_email');

    function haven_order_confirm_page($order_id) {
        Haven_Gateway::customer_order_page($order_id);
    }
    function haven_order_page($order) {
        if(!is_wc_endpoint_url('order-received'))
            Haven_Gateway::customer_order_page($order);
    }
    function haven_order_email($order) {
        Haven_Gateway::customer_order_email($order);
    }

    add_action('wc_ajax_haven_gateway_payment_details', 'haven_get_payment_details_ajax');
    function haven_get_payment_details_ajax() {
        Haven_Gateway::get_payment_details_ajax();
    }

    //Add them to the choice list of currency in admin
    add_filter('woocommerce_currencies', 'haven_add_currency');
    function haven_add_currency($currencies) {
        foreach(HAVEN_XASSETS as $xAsset => $options){
          $currencies[$xAsset] = __($xAsset, 'haven_gateway');
        }

        return $currencies;
    }

    add_filter('woocommerce_currency_symbol', 'haven_add_currency_symbol', 10, 2);
    function haven_add_currency_symbol($currency_symbol, $currency) {
      if(!empty(HAVEN_XASSETS[$currency]['symbol'])){
        $currency_symbol = HAVEN_XASSETS[$currency]['symbol'];
      }
      return $currency_symbol;
    }

    add_action('wp_enqueue_scripts', 'haven_enqueue_scripts');
    function haven_enqueue_scripts() {
        if(Haven_Gateway::use_qr_code())
            wp_enqueue_script('haven-qr-code', HAVEN_GATEWAY_PLUGIN_URL.'assets/js/qrcode.min.js');

        wp_enqueue_script('haven-clipboard-js', HAVEN_GATEWAY_PLUGIN_URL.'assets/js/clipboard.min.js');
        wp_enqueue_script('haven-gateway', HAVEN_GATEWAY_PLUGIN_URL.'assets/js/haven-gateway-order-page.js');
        wp_enqueue_style('haven-gateway', HAVEN_GATEWAY_PLUGIN_URL.'assets/css/haven-gateway-order-page.css');
    }

    // [haven-accepted-here]
    function haven_accepted_func() {
        return '<img src="'.HAVEN_GATEWAY_PLUGIN_URL.'assets/images/haven-accepted-here2x.png" />';
    }
    add_shortcode('haven-accepted-here', 'haven_accepted_func');

}

register_deactivation_hook(__FILE__, 'haven_deactivate');
function haven_deactivate() {
    $timestamp = wp_next_scheduled('haven_update_event');
    wp_unschedule_event($timestamp, 'haven_update_event');
}

register_activation_hook(__FILE__, 'haven_install');
function haven_install() {
    global $wpdb;
    require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . "haven_gateway_quotes";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
               order_id BIGINT(20) UNSIGNED NOT NULL,
               payment_id VARCHAR(98) DEFAULT '' NOT NULL,
               currency VARCHAR(6) DEFAULT '' NOT NULL,
               rate BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               amount BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               paid TINYINT NOT NULL DEFAULT 0,
               confirmed TINYINT NOT NULL DEFAULT 0,
               pending TINYINT NOT NULL DEFAULT 1,
               created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (order_id)
               ) $charset_collate;";
        dbDelta($sql);
    }

    $table_name = $wpdb->prefix . "haven_gateway_quotes_txids";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
               id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
               payment_id VARCHAR(98) DEFAULT '' NOT NULL,
               txid VARCHAR(64) DEFAULT '' NOT NULL,
               amount BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               currency VARCHAR(20) DEFAULT '' NOT NULL,
               height MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
               PRIMARY KEY (id),
               UNIQUE KEY (payment_id, txid, amount)
               ) $charset_collate;";
        dbDelta($sql);
    }

    $table_name = $wpdb->prefix . "haven_gateway_live_rates";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
               currency VARCHAR(6) DEFAULT '' NOT NULL,
               rate BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (currency)
               ) $charset_collate;";
        dbDelta($sql);
    }
}
