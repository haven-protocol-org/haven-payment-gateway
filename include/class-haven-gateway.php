<?php
/*
 * Main Gateway of Haven using either a local daemon or the explorer
 * Authors: zrero, SerHack, cryptochangements, mosu-forge
 */

defined( 'ABSPATH' ) || exit;

require_once('class-haven-cryptonote.php');

class Haven_Gateway extends WC_Payment_Gateway
{
    private static $_id = 'haven_gateway';
    private static $_title = 'Haven Protocol Gateway';
    private static $_method_title = 'Haven Protocol Gateway';
    private static $_method_description = 'Haven Protocol Gateway Plug-in for WooCommerce.';
    private static $_errors = [];

    private static $discount = false;
    private static $valid_time = null;
    private static $confirms = null;
    private static $confirm_type = null;
    private static $address = null;
    private static $viewkey = null;
    private static $host = null;
    private static $port = null;
    private static $testnet = false;
    private static $onion_service = false;
    private static $show_qr = false;

    private static $cryptonote;
    private static $haven_wallet_rpc;
    private static $haven_explorer_tools;
    private static $log;

    private static $rates = array();

    private static $payment_details = array();

    public function get_icon()
    {
        return apply_filters('woocommerce_gateway_icon', '<img src="'.HAVEN_GATEWAY_PLUGIN_URL.'assets/images/haven-icon.png"/>', $this->id);
    }

    function __construct($add_action=true)
    {
        $this->id = self::$_id;
        $this->method_title = __(self::$_method_title, 'haven_gateway');
        $this->method_description = __(self::$_method_description, 'haven_gateway');
        $this->has_fields = false;
        $this->supports = array(
            'products',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'subscription_payment_method_change'
        );

        $this->enabled = $this->get_option('enabled') == 'yes';

        $this->init_form_fields();
        $this->init_settings();

        self::$_title = $this->settings['title'];
        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        self::$discount = $this->settings['discount'];
        self::$valid_time = $this->settings['valid_time'];
        self::$confirms = $this->settings['confirms'];
        self::$confirm_type = $this->settings['confirm_type'];
        self::$address = $this->settings['haven_address'];
        self::$viewkey = $this->settings['viewkey'];
        self::$host = $this->settings['daemon_host'];
        self::$port = $this->settings['daemon_port'];
        self::$testnet = $this->settings['testnet'] == 'yes';
        self::$onion_service = $this->settings['onion_service'] == 'yes';
        self::$show_qr = $this->settings['show_qr'] == 'yes';

        $explorer_url = self::$testnet ? HAVEN_GATEWAY_TESTNET_EXPLORER_URL : HAVEN_GATEWAY_MAINNET_EXPLORER_URL;
        defined('HAVEN_GATEWAY_EXPLORER_URL') || define('HAVEN_GATEWAY_EXPLORER_URL', $explorer_url);
        defined('HAVEN_GATEWAY_ADDRESS_PREFIX') || define('HAVEN_GATEWAY_ADDRESS_PREFIX', self::$testnet ? HAVEN_GATEWAY_TESTNET_ADDRESS_PREFIX : HAVEN_GATEWAY_MAINNET_ADDRESS_PREFIX);
        defined('HAVEN_GATEWAY_ADDRESS_PREFIX_INTEGRATED') || define('HAVEN_GATEWAY_ADDRESS_PREFIX_INTEGRATED', self::$testnet ? HAVEN_GATEWAY_TESTNET_ADDRESS_PREFIX_INTEGRATED : HAVEN_GATEWAY_MAINNET_ADDRESS_PREFIX_INTEGRATED);

        if($add_action)
            add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));

        // Initialize helper classes
        self::$cryptonote = new Haven_Cryptonote();
        if(self::$confirm_type == 'haven-wallet-rpc') {
            require_once('class-haven-wallet-rpc.php');
            self::$haven_wallet_rpc = new Haven_Wallet_Rpc(self::$host, self::$port);
        } else {
            require_once('class-haven-explorer-tools.php');
            self::$haven_explorer_tools = new Haven_Explorer_Tools();
        }

        self::$log = new WC_Logger();
    }

    public function init_form_fields()
    {
        $this->form_fields = include 'admin/haven-gateway-admin-settings.php';
    }

    public function validate_haven_address_field($key,$address)
    {
        if($this->settings['confirm_type'] == 'viewkey') {
            if (strlen($address) == 98 && substr($address, 0, 2) == 'hv')
                if(self::$cryptonote->verify_checksum($address))
                    return $address;
            self::$_errors[] = 'Haven Protocol address is invalid';
        }
        return $address;
    }

    public function validate_viewkey_field($key,$viewkey)
    {
        if($this->settings['confirm_type'] == 'viewkey') {
            if(preg_match('/^[a-z0-9]{64}$/i', $viewkey)) {
                return $viewkey;
            } else {
                self::$_errors[] = 'Viewkey is invalid';
                return '';
            }
        }
        return $viewkey;
    }

    public function validate_confirms_field($key,$confirms)
    {
        if($confirms >= 0 && $confirms <= 60)
            return $confirms;
        self::$_errors[] = 'Number of confirms must be between 0 and 60';
    }

    public function validate_valid_time_field($key,$valid_time)
    {
        if($valid_time >= 600 && $valid_time < 86400*7)
            return $valid_time;
        self::$_errors[] = 'Order valid time must be between 600 (10 minutes) and 604800 (1 week)';
    }

    public function admin_options()
    {
        $confirm_type = self::$confirm_type;
        if($confirm_type === 'haven-wallet-rpc')
            $balance = self::admin_balance_info();

        $settings_html = $this->generate_settings_html(array(), false);
        $errors = array_merge(self::$_errors, $this->admin_php_module_check(), $this->admin_ssl_check());
        include HAVEN_GATEWAY_PLUGIN_DIR . '/templates/haven-gateway/admin/settings-page.php';
    }

    public static function admin_balance_info()
    {
        if(!is_admin()) {
            return array(
                'height' => 'Not Available',
                'balance' => 'Not Available',
                'unlocked_balance' => 'Not Available',
            );
        }
        $wallet_amount = self::$haven_wallet_rpc->getbalance();
        $height = self::$haven_wallet_rpc->getheight();
        if (!isset($wallet_amount)) {
            self::$_errors[] = 'Cannot connect to haven-wallet-rpc';
            self::$log->add('Haven_Payments', '[ERROR] Cannot connect to haven-wallet-rpc');
            return array(
                'height' => 'Not Available',
                'balance' => 'Not Available',
                'unlocked_balance' => 'Not Available',
            );
        } else {
            return array(
                'height' => $height,
                'balance' => self::format_haven($wallet_amount['balance']).' Haven',
                'unlocked_balance' => self::format_haven($wallet_amount['unlocked_balance']).' Haven'
            );
        }
    }

    protected function admin_ssl_check()
    {
        $errors = array();
        if ($this->enabled && !self::$onion_service)
            if (get_option('woocommerce_force_ssl_checkout') == 'no')
                $errors[] = sprintf('%s is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href="%s">forcing the checkout pages to be secured.</a>', self::$_method_title, admin_url('admin.php?page=wc-settings&tab=checkout'));
        return $errors;
    }

    protected function admin_php_module_check()
    {
        $errors = array();
        if(!extension_loaded('bcmath'))
            $errors[] = 'PHP extension bcmath must be installed';
        return $errors;
    }

    public function process_payment($order_id)
    {
        global $wpdb, $xAssetSelected;
        $table_name = $wpdb->prefix.'haven_gateway_quotes';

        $order = wc_get_order($order_id);

        if(self::$confirm_type != 'haven-wallet-rpc') {
          // Generate a unique payment id
          do {
              $payment_id = bin2hex(openssl_random_pseudo_bytes(8));
              $query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE payment_id=%s", array($payment_id));
              $payment_id_used = $wpdb->get_var($query);
          } while ($payment_id_used);
        }
        else {
          // Generate subaddress
          $payment_id = self::$haven_wallet_rpc->create_address(0, 'Order: ' . $order_id);
          if(isset($payment_id['address'])) {
            $payment_id = $payment_id['address'];
          }
          else {
            $this->log->add('Haven_Gateway', 'Couldn\'t create subaddress for order ' . $order_id);
          }
        }

        $haven_amount = $order->get_total(''); //TODO Is this the right amount????

        if(self::$discount)
            $haven_amount = $haven_amount - $haven_amount * self::$discount / 100;

        $haven_amount = intval($haven_amount * HAVEN_GATEWAY_ATOMIC_UNITS_POW);

        $query = $wpdb->prepare("INSERT INTO $table_name (order_id, payment_id, currency, rate, amount) VALUES (%d, %s, %s, %d, %d)", array($order_id, $payment_id, $xAssetSelected, $rate, $haven_amount));
        $wpdb->query($query);

        $order->update_status('on-hold', __('Awaiting offline payment', 'haven_gateway'));
        $order->reduce_order_stock(); // Reduce stock levels
        WC()->cart->empty_cart(); // Remove cart

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    /*
     * function for verifying payments
     * This cron runs every 30 seconds
     */
    public static function do_update_event()
    {
        global $wpdb;

        // Get current network/wallet height
        if(self::$confirm_type == 'haven-wallet-rpc')
            $height = self::$haven_wallet_rpc->getheight();
        else
            $height = self::$haven_explorer_tools->getheight();
        set_transient('haven_gateway_network_height', $height);

        // Get pending payments
        $table_name_1 = $wpdb->prefix.'haven_gateway_quotes';
        $table_name_2 = $wpdb->prefix.'haven_gateway_quotes_txids';

        $query = $wpdb->prepare("SELECT *, $table_name_1.payment_id AS payment_id, $table_name_1.amount AS amount_total, $table_name_2.amount AS amount_paid, NOW() as now FROM $table_name_1 LEFT JOIN $table_name_2 ON $table_name_1.payment_id = $table_name_2.payment_id WHERE pending=1", array());

        $rows = $wpdb->get_results($query);

        $pending_payments = array();

        // Group the query into distinct orders by payment_id
        foreach($rows as $row) {
            if(!isset($pending_payments[$row->payment_id]))
                $pending_payments[$row->payment_id] = array(
                    'quote' => null,
                    'txs' => array()
                );
            $pending_payments[$row->payment_id]['quote'] = $row;
            if($row->txid)
                $pending_payments[$row->payment_id]['txs'][] = $row;
        }

        // Loop through each pending payment and check status
        foreach($pending_payments as $pending) {
            $quote = $pending['quote'];
            $old_txs = $pending['txs'];
            $order_id = $quote->order_id;
            $order = wc_get_order($order_id);
            $payment_id = self::sanatize_id($quote->payment_id);
            $amount_haven = $quote->amount_total;

            if(self::$confirm_type == 'haven-wallet-rpc')
                $new_txs = self::check_payment_rpc($payment_id);
            else
                $new_txs = self::check_payment_explorer($payment_id);

            foreach($new_txs as $new_tx) {
                $is_new_tx = true;
                foreach($old_txs as $old_tx) {
                    if($new_tx['txid'] == $old_tx->txid && $new_tx['amount'] == $old_tx->amount_paid && $new_tx['currency'] == $old_tx->currency) {
                        $is_new_tx = false;
                        break;
                    }
                }
                if($is_new_tx) {
                    $old_txs[] = (object) $new_tx;
                }

                $query = $wpdb->prepare("INSERT INTO $table_name_2 (payment_id, txid, currency, amount, height) VALUES (%s, %s, %s, %d, %d) ON DUPLICATE KEY UPDATE height=%d", array($payment_id, $new_tx['txid'], $new_tx['currency'], $new_tx['amount'], $new_tx['height'], $new_tx['height']));
                $wpdb->query($query);
            }

            $txs = $old_txs;
            $heights = array();
            $amount_paid = 0;
            foreach($txs as $tx) {
                if($quote->currency == $tx->currency){
                  $amount_paid += $tx->amount;
                }
                $heights[] = $tx->height;
            }

            $paid = $amount_paid > $amount_haven - HAVEN_GATEWAY_ATOMIC_UNIT_THRESHOLD;

            if($paid) {
                if(self::$confirms == 0) {
                    $confirmed = true;
                } else {
                    $highest_block = max($heights);
                    if($height - $highest_block >= self::$confirms && !in_array(0, $heights)) {
                        $confirmed = true;
                    } else {
                        $confirmed = false;
                    }
                }
            } else {
                $confirmed = false;
            }

            if($paid && $confirmed) {
                self::$log->add('Haven_Payments', "[SUCCESS] Payment has been confirmed for order id $order_id and payment id $payment_id (currency: $quote->currency)");
                $query = $wpdb->prepare("UPDATE $table_name_1 SET confirmed=1,paid=1,pending=0 WHERE payment_id=%s", array($payment_id));
                $wpdb->query($query);

                unset(self::$payment_details[$order_id]);

                if(self::is_virtual_in_cart($order_id) == true){
                    $order->update_status('completed', __('Payment has been received.', 'haven_gateway'));
                } else {
                    $order->update_status('processing', __('Payment has been received.', 'haven_gateway'));
                }

            } else if($paid) {
                self::$log->add('Haven_Payments', "[SUCCESS] Payment has been received for order id $order_id and payment id $payment_id");
                $query = $wpdb->prepare("UPDATE $table_name_1 SET paid=1 WHERE payment_id=%s", array($payment_id));
                $wpdb->query($query);

                unset(self::$payment_details[$order_id]);

            } else {
                $timestamp_created = new DateTime($quote->created);
                $timestamp_now = new DateTime($quote->now);
                $order_age_seconds = $timestamp_now->getTimestamp() - $timestamp_created->getTimestamp();
                if($order_age_seconds > self::$valid_time) {
                    self::$log->add('Haven_Payments', "[FAILED] Payment has expired for order id $order_id and payment id $payment_id");
                    $query = $wpdb->prepare("UPDATE $table_name_1 SET pending=0 WHERE payment_id=%s", array($payment_id));
                    $wpdb->query($query);

                    unset(self::$payment_details[$order_id]);

                    $order->update_status('cancelled', __('Payment has expired.', 'haven_gateway'));
                }
            }
        }
    }

    protected static function check_payment_rpc($subaddress)
    {
        $txs = array();
        $address_index = self::$haven_wallet_rpc->get_address_index($subaddress);
        if(isset($address_index['index']['minor'])){
          $address_index = $address_index['index']['minor'];
        }
        else {
          self::$log->add('Haven_Gateway', '[ERROR] Couldn\'t get address index of subaddress: ' . $subaddress);
          return $txs;
        }
        $payments = self::$haven_wallet_rpc->get_transfers(array( 'in' => true, 'pool' => true, 'subaddr_indices' => array($address_index)));
        if(isset($payments['in'])) {
          foreach($payments['in'] as $payment) {
              $txs[] = array(
                  'amount' => $payment['amount'],
                  'currency' => $payment['currency'],
                  'txid' => $payment['txid'],
                  'height' => $payment['height']
              );
          }
        }
        if(isset($payments['pool'])) {
          foreach($payments['pool'] as $payment) {
              $txs[] = array(
                  'amount' => $payment['amount'],
                  'currency' => $payment['currency'],
                  'txid' => $payment['txid'],
                  'height' => $payment['height']
              );
          }
        }
        return $txs;
    }

    public static function check_payment_explorer($payment_id)
    {
        $txs = array();
        $outputs = self::$haven_explorer_tools->get_outputs(self::$address, self::$viewkey);
        foreach($outputs as $payment) {
            if($payment['payment_id'] == $payment_id) {
                $txs[] = array(
                    'amount' => $payment['amount'],
                    'currency' => $payment['currency'],
                    'txid' => $payment['tx_hash'],
                    'height' => $payment['block_no']
                );
            }
        }
        return $txs;
    }

    protected static function get_payment_details($order_id)
    {
        if(!is_integer($order_id))
            $order_id = $order_id->get_id();

        if(isset(self::$payment_details[$order_id]))
            return self::$payment_details[$order_id];

        global $wpdb;
        $table_name_1 = $wpdb->prefix.'haven_gateway_quotes';
        $table_name_2 = $wpdb->prefix.'haven_gateway_quotes_txids';
        $query = $wpdb->prepare("SELECT *, $table_name_1.currency as currency, $table_name_1.payment_id AS payment_id, $table_name_1.amount AS amount_total, $table_name_2.amount AS amount_paid, NOW() as now FROM $table_name_1 LEFT JOIN $table_name_2 ON $table_name_1.payment_id = $table_name_2.payment_id WHERE order_id=%d", array($order_id));
        $details = $wpdb->get_results($query);
        if (count($details)) {
            $txs = array();
            $heights = array();
            $amount_paid = 0;
            foreach($details as $tx) {
                if(!isset($tx->txid))
                    continue;
                $txs[] = array(
                    'txid' => $tx->txid,
                    'height' => $tx->height,
                    'currency' => $tx->currency,
                    'amount' => $tx->amount_paid,
                    'amount_formatted' => self::format_haven($tx->amount_paid)
                );
                $amount_paid += $tx->amount_paid;
                $heights[] = $tx->height;
            }

            usort($txs, function($a, $b) {
                if($a['height'] == 0) return -1;
                return $b['height'] - $a['height'];
            });

            if(count($heights) && !in_array(0, $heights)) {
                $height = get_transient('haven_gateway_network_height');
                $highest_block = max($heights);
                $confirms = $height - $highest_block;
                $blocks_to_confirm = self::$confirms - $confirms;
            } else {
                $blocks_to_confirm = self::$confirms;
            }
            $time_to_confirm = self::format_seconds_to_time($blocks_to_confirm * HAVEN_GATEWAY_DIFFICULTY_TARGET);

            $amount_total = $details[0]->amount_total;
            $amount_due = max(0, $amount_total - $amount_paid);

            $timestamp_created = new DateTime($details[0]->created);
            $timestamp_now = new DateTime($details[0]->now);

            $order_age_seconds = $timestamp_now->getTimestamp() - $timestamp_created->getTimestamp();
            $order_expires_seconds = self::$valid_time - $order_age_seconds;

            $address = self::$address;
            $payment_id = self::sanatize_id($details[0]->payment_id);

            if(self::$confirm_type == 'haven-wallet-rpc') {
                $integrated_addr = $payment_id;
            } else {
                if ($address) {
                    $decoded_address = self::$cryptonote->decode_address($address);
                    $pub_spendkey = $decoded_address['spendkey'];
                    $pub_viewkey = $decoded_address['viewkey'];
                    $integrated_addr = self::$cryptonote->integrated_addr_from_keys($pub_spendkey, $pub_viewkey, $payment_id);
                } else {
                    self::$log->add('Haven_Gateway', '[ERROR] Merchant has not set Haven Protocol address');
                    return '[ERROR] Merchant has not set Haven Protocol address';
                }
            }

            $status = '';
            $paid = $details[0]->paid == 1;
            $confirmed = $details[0]->confirmed == 1;
            $pending = $details[0]->pending == 1;

            if($confirmed) {
                $status = 'confirmed';
            } else if($paid) {
                $status = 'paid';
            } else if($pending && $order_expires_seconds > 0) {
                if(count($txs)) {
                    $status = 'partial';
                } else {
                    $status = 'unpaid';
                }
            } else {
                if(count($txs)) {
                    $status = 'expired_partial';
                } else {
                    $status = 'expired';
                }
            }

            $amount_formatted = self::format_haven($amount_due);
            $qrcode_uri = 'haven:'.$address.'?tx_amount='.$amount_formatted.'&tx_payment_id='.$payment_id;
            $my_order_url = wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount'));

            $payment_details = array(
                'order_id' => $order_id,
                'payment_id' => $payment_id,
                'integrated_address' => $integrated_addr,
                'qrcode_uri' => $qrcode_uri,
                'my_order_url' => $my_order_url,
                'rate' => $details[0]->rate,
                'rate_formatted' => sprintf('%.8f', $details[0]->rate / 1e8),
                'currency' => $details[0]->currency,
                'amount_total' => $amount_total,
                'amount_paid' => $amount_paid,
                'amount_due' => $amount_due,
                'amount_total_formatted' => self::format_haven($amount_total),
                'amount_paid_formatted' => self::format_haven($amount_paid),
                'amount_due_formatted' => self::format_haven($amount_due),
                'status' => $status,
                'created' => $details[0]->created,
                'order_age' => $order_age_seconds,
                'order_expires' => self::format_seconds_to_time($order_expires_seconds),
                'blocks_to_confirm' => $blocks_to_confirm,
                'time_to_confirm' => $time_to_confirm,
                'txs' => $txs
            );
            self::$payment_details[$order_id] = $payment_details;
            return $payment_details;
        } else {
            return '[ERROR] Quote not found';
        }

    }

    public static function get_payment_details_ajax() {

        $user = wp_get_current_user();
        if($user === 0)
            self::ajax_output(array('error' => '[ERROR] User not logged in'));

        $order_id = preg_replace("/[^0-9]+/", "", $_GET['order_id']);
        $order = wc_get_order( $order_id );

        if($order->get_customer_id() != $user->ID)
            self::ajax_output(array('error' => '[ERROR] Order does not belong to this user'));

        if($order->get_payment_method() != self::$_id)
            self::ajax_output(array('error' => '[ERROR] Order not paid for with Haven'));

        $details = self::get_payment_details($order);
        if(!is_array($details))
            self::ajax_output(array('error' => $details));

        self::ajax_output($details);

    }
    public static function ajax_output($response) {
        ob_clean();
        header('Content-type: application/json');
        echo json_encode($response);
        wp_die();
    }

    public static function admin_order_page($post)
    {
        $order = wc_get_order($post->ID);
        if($order->get_payment_method() != self::$_id)
            return;

        $method_title = self::$_title;
        $details = self::get_payment_details($order);
        if(!is_array($details)) {
            $error = $details;
            include HAVEN_GATEWAY_PLUGIN_DIR . '/templates/haven-gateway/admin/order-history-error-page.php';
            return;
        }
        include HAVEN_GATEWAY_PLUGIN_DIR . '/templates/haven-gateway/admin/order-history-page.php';
    }

    public static function customer_order_page($order)
    {
        if(is_integer($order)) {
            $order_id = $order;
            $order = wc_get_order($order_id);
        } else {
            $order_id = $order->get_id();
        }

        if($order->get_payment_method() != self::$_id)
            return;

        $method_title = self::$_title;
        $details = self::get_payment_details($order_id);
        if(!is_array($details)) {
            $error = $details;
            include HAVEN_GATEWAY_PLUGIN_DIR . '/templates/haven-gateway/customer/order-error-page.php';
            return;
        }
        $show_qr = self::$show_qr;
        $details_json = json_encode($details);
        $ajax_url = WC_AJAX::get_endpoint('haven_gateway_payment_details');
        include HAVEN_GATEWAY_PLUGIN_DIR . '/templates/haven-gateway/customer/order-page.php';
    }

    public static function customer_order_email($order)
    {
        if(is_integer($order)) {
            $order_id = $order;
            $order = wc_get_order($order_id);
        } else {
            $order_id = $order->get_id();
        }

        if($order->get_payment_method() != self::$_id)
            return;

        $method_title = self::$_title;
        $details = self::get_payment_details($order_id);
        if(!is_array($details)) {
            include HAVEN_GATEWAY_PLUGIN_DIR . '/templates/haven-gateway/customer/order-email-error-block.php';
            return;
        }
        include HAVEN_GATEWAY_PLUGIN_DIR . '/templates/haven-gateway/customer/order-email-block.php';
    }

    public static function get_id()
    {
        return self::$_id;
    }

    public static function get_confirm_type()
    {
        return self::$confirm_type;
    }

    public static function use_qr_code()
    {
        return self::$show_qr;
    }

    protected static function sanatize_id($payment_id)
    {
        // Limit payment id to alphanumeric characters
        $sanatized_id = preg_replace("/[^a-zA-Z0-9]+/", "", $payment_id);
        return $sanatized_id;
    }

    protected static function is_virtual_in_cart($order_id)
    {
        $order = wc_get_order($order_id);
        $items = $order->get_items();
        $cart_size = count($items);
        $virtual_items = 0;

        foreach ( $items as $item ) {
            $product = new WC_Product( $item['product_id'] );
            if ($product->is_virtual()) {
                $virtual_items += 1;
            }
        }
        return $virtual_items == $cart_size;
    }

    public static function format_haven($atomic_units) {
        return sprintf(HAVEN_GATEWAY_ATOMIC_UNITS_SPRINTF, $atomic_units / HAVEN_GATEWAY_ATOMIC_UNITS_POW);
    }

    public static function format_seconds_to_time($seconds)
    {
        $units = array();

        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        $diff = $dtF->diff($dtT);

        $d = $diff->format('%a');
        $h = $diff->format('%h');
        $m = $diff->format('%i');

        if($d == 1)
            $units[] = "$d day";
        else if($d > 1)
            $units[] = "$d days";

        if($h == 0 && $d != 0)
            $units[] = "$h hours";
        else if($h == 1)
            $units[] = "$h hour";
        else if($h > 0)
            $units[] = "$h hours";

        if($m == 1)
            $units[] = "$m minute";
        else
            $units[] = "$m minutes";

        return implode(', ', $units) . ($seconds < 0 ? ' ago' : '');
    }

}
