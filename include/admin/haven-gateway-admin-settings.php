<?php

defined( 'ABSPATH' ) || exit;

return array(
    'enabled' => array(
        'title' => __('Enable / Disable', 'haven_gateway'),
        'label' => __('Enable this payment gateway', 'haven_gateway'),
        'type' => 'checkbox',
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title', 'haven_gateway'),
        'type' => 'text',
        'desc_tip' => __('Payment title the customer will see during the checkout process.', 'haven_gateway'),
        'default' => __('Haven Protocol Gateway', 'haven_gateway')
    ),
    'description' => array(
        'title' => __('Description', 'haven_gateway'),
        'type' => 'textarea',
        'desc_tip' => __('Payment description the customer will see during the checkout process.', 'haven_gateway'),
        'default' => __('Pay securely using the Haven Protocol. You will be provided payment details after checkout.', 'haven_gateway')
    ),
    'discount' => array(
        'title' => __('Discount for using Haven', 'haven_gateway'),
        'desc_tip' => __('Provide a discount to your customers for making a private payment with Haven', 'haven_gateway'),
        'description' => __('Enter a percentage discount (i.e. 5 for 5%) or leave this empty if you do not wish to provide a discount', 'haven_gateway'),
        'type' => __('number'),
        'default' => '0'
    ),
    'valid_time' => array(
        'title' => __('Order valid time', 'haven_gateway'),
        'desc_tip' => __('Amount of time order is valid before expiring', 'haven_gateway'),
        'description' => __('Enter the number of seconds that the funds must be received in after order is placed. 3600 seconds = 1 hour', 'haven_gateway'),
        'type' => __('number'),
        'default' => '3600'
    ),
    'confirms' => array(
        'title' => __('Number of confirmations', 'haven_gateway'),
        'desc_tip' => __('Number of confirms a transaction must have to be valid', 'haven_gateway'),
        'description' => __('Enter the number of confirms that transactions must have. Enter 0 to zero-confim. Each confirm will take approximately four minutes', 'haven_gateway'),
        'type' => __('number'),
        'default' => '5'
    ),
    'confirm_type' => array(
        'title' => __('Confirmation Type', 'haven_gateway'),
        'desc_tip' => __('Select the method for confirming transactions', 'haven_gateway'),
        'description' => __('Select the method for confirming transactions', 'haven_gateway'),
        'type' => 'select',
        'options' => array(
            'viewkey'        => __('viewkey', 'haven_gateway'),
            'haven-wallet-rpc' => __('haven-wallet-rpc', 'haven_gateway')
        ),
        'default' => 'viewkey'
    ),
    'haven_address' => array(
        'title' => __('Haven Protocol Address', 'haven_gateway'),
        'label' => __('Useful for people that have not a daemon online'),
        'type' => 'text',
        'desc_tip' => __('Haven Protocol Wallet Address', 'haven_gateway')
    ),
    'viewkey' => array(
        'title' => __('Secret Viewkey', 'haven_gateway'),
        'label' => __('Secret Viewkey'),
        'type' => 'text',
        'desc_tip' => __('Your secret Viewkey', 'haven_gateway')
    ),
    'daemon_host' => array(
        'title' => __('Haven Protocol wallet RPC Host/IP', 'haven_gateway'),
        'type' => 'text',
        'desc_tip' => __('This is the Daemon Host/IP to authorize the payment with', 'haven_gateway'),
        'default' => '127.0.0.1',
    ),
    'daemon_port' => array(
        'title' => __('Haven Protocol wallet RPC port', 'haven_gateway'),
        'type' => __('number'),
        'desc_tip' => __('This is the Wallet RPC port to authorize the payment with', 'haven_gateway'),
        'default' => '18080',
    ),
    'testnet' => array(
        'title' => __(' Testnet', 'haven_gateway'),
        'label' => __(' Check this if you are using testnet ', 'haven_gateway'),
        'type' => 'checkbox',
        'description' => __('Advanced usage only', 'haven_gateway'),
        'default' => 'no'
    ),
    'javascript' => array(
        'title' => __(' Javascript', 'haven_gateway'),
        'label' => __(' Check this to ENABLE Javascript in Checkout page ', 'haven_gateway'),
        'type' => 'checkbox',
        'default' => 'no'
     ),
    'onion_service' => array(
        'title' => __(' SSL warnings ', 'haven_gateway'),
        'label' => __(' Check to Silence SSL warnings', 'haven_gateway'),
        'type' => 'checkbox',
        'description' => __('Check this box if you are running on an Onion Service (Suppress SSL errors)', 'haven_gateway'),
        'default' => 'no'
    ),
    'show_qr' => array(
        'title' => __('Show QR Code', 'haven_gateway'),
        'label' => __('Show QR Code', 'haven_gateway'),
        'type' => 'checkbox',
        'description' => __('Enable this to show a QR code after checkout with payment details.'),
        'default' => 'no'
    ),
    /*'use_haven_price' => array(
        'title' => __('Show Prices in XHV', 'haven_gateway'),
        'label' => __('Show Prices in XHV', 'haven_gateway'),
        'type' => 'checkbox',
        'description' => __('Enable this to convert ALL prices on the frontend to Haven Protocol (experimental)'),
        'default' => 'no'
    ),*/
    'use_haven_price_decimals' => array(
        'title' => __('Display Decimals', 'haven_gateway'),
        'type' => __('number'),
        'description' => __('Number of decimal places to display on frontend. Upon checkout exact price will be displayed.'),
        'default' => 12,
    ),
);
