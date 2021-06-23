=== Haven Payment Gateway ===
Contributors: Marty, zrero, bluey.red
Donate link: https://havenprotocol.org/donations/
Tags: xhv, xusd, haven, WooCommerce, integration, payment, merchant, cryptocurrency, accept haven, accept xhv, xhv woocommerce
Requires at least: 4.0
Tested up to: 5.7.2
Stable tag: 1.0.1
License: MIT license
License URI: https://github.com/haven-protocol-org/haven-woocommerce-gateway/blob/master/LICENSE
 
Haven WooCommerce Payment Gateway is a Wordpress plugin that allows users to accept xUSD in WooCommerce-powered online stores.

== Description ==

Your online store must use WooCommerce platform.  Once you installed and activated WooCommerce, you may install and activate Haven WooCommerce Extension.

This plugin supports stores with pricing in a supported xAsset, currently USD, GBP, EUR, CNY, GOLD. 

If your store is not in a supported currency, a multi-currency plugin can be used. 

= Benefits =

* Accept payment in xUSD directly into your personal Haven wallet.
* Accept payment in Haven xUSD for physical and digital downloadable products.
* Add Haven xUSD payments option to your existing online store.
* Flexible exchange rate calculations fully managed via administrative settings.
* Zero fees and no commissions for xUSD payments processing from any third party.
* Automatic conversion to Haven via realtime exchange rate feed and calculations.
* Ability to set exchange rate calculation multiplier to compensate for any possible losses due to bank conversions and funds transfer fees.

== Installation ==

1. Install "Haven Payment Gateway" wordpress plugin just like any other Wordpress plugin.
2. Activate "Haven Protocol Woocommerce Gateway" in your WordPress admin dashboard.
3. It is highly recommended that you use native cronjobs instead of WordPress's "Poor Man's Cron" by adding `define('DISABLE_WP_CRON', true);` into your `wp-config.php` file and adding `* * * * * wget -q -O - https://yourstore.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1` to your crontab.
4. Your store's currency needs to be set to a supported xAsset currency in WooCommerce > Settings > General - Currency  (currently USD, EUR, CNY, GOLD)
5. Switch on Haven as a Payment method in WooCommerce > Settings > Payment
* Note: The receiving wallet should be not be used to make payments to the store.

== Remove plugin ==

1. Deactivate plugin through the 'Plugins' menu in WordPress
2. Delete plugin through the 'Plugins' menu in WordPress

== Screenshots == 

1. Payment Box
2. Options

== Changelog ==

0.0.1 Initial plugin
1.0.0 Wordpress directory unification
1.0.1 Table creation sql bugfix

== Frequently Asked Questions ==

* What is Haven?
Haven Protocol decentralised algorithmic synthetic asset platform based on the Monero codebase. The idea was for a private coin that anyone could use anywhere with no 3rd parties, with the ability to convert between currencies in your own digital vault. Many described it as the ultimate expression of what a cryptocurrency should be. 

See https://havenprotocol.org/ for more information

* What is a Haven wallet?

A Haven wallet is a piece of software that allows you to create an Haven account, store your funds and interact with the Haven network. You can get a Haven wallet from https://github.com/haven-protocol-org/haven-offshore/releases/latest

* What is haven-wallet-rpc ?
The haven-wallet-rpc is an RPC server that will allow this plugin to communicate with the Haven network. You can download it from https://github.com/haven-protocol-org/haven-offshore/releases/latest with the command-line tools.

* Setting up a wallet
The easiest and quickest way is to visit the Haven web wallet at https://vault.havenprotocol.org/ for an in depth guide visit the Haven Protocol knowledge base at https://havenprotocol.org/knowledge/create-account/ 

* Why do I see `[ERROR] Failed to connect to haven-wallet-rpc at localhost port 18080
Syntax error: Invalid response data structure: Request id: 1 is different from Response id: ` ?
This is most likely because this plugin can not reach your haven-wallet-rpc. Make sure that you have supplied the correct host IP and port to the plugin in their fields. If your haven-wallet-rpc is on a different server than your wordpress site, make sure that the appropriate port is open with port forwarding enabled.

* How can I get support?
Support is available in the support channel of Haven Discord: https://discordapp.com/invite/CCtNxfG