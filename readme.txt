=== Haven WooCommerce Extension ===
Contributors: Marty, zrero
Donate link: https://havenprotocol.org/donations/
Tags: xhv, xusd, haven, WooCommerce, integration, payment, merchant, cryptocurrency, accept haven, accept xhv, xhv woocommerce
Requires at least: 4.0
Tested up to: 5.6
Stable tag: trunk
License: MIT license
License URI: https://github.com/haven-protocol-org/haven-woocommerce-gateway/blob/master/LICENSE
 
Haven WooCommerce Extension is a Wordpress plugin that allows users to accept xUSD in WooCommerce-powered online stores.

== Description ==

Your online store must use WooCommerce platform.  Once you installed and activated WooCommerce, you may install and activate Haven WooCommerce Extension.

Currently, this plugin will only support stores with pricing in US Dollars. These prices will be converted into xUSD during checkout, at an exchange rate of 1$USD = 1xUSD.

If your store is not in USD, a multi-currency plugin can be used. 

In the future other currencies, such as EUR, GBP, JPY, and CNY, will be supported.

= Benefits =

* Accept payment in xUSD directly into your personal Haven wallet.
* Accept payment in Haven xUSD for physical and digital downloadable products.
* Add Haven xUSD payments option to your existing online store.
* Flexible exchange rate calculations fully managed via administrative settings.
* Zero fees and no commissions for xUSD payments processing from any third party.
* Automatic conversion to Monero via realtime exchange rate feed and calculations.
* Ability to set exchange rate calculation multiplier to compensate for any possible losses due to bank conversions and funds transfer fees.

== Installation ==

1. Install "Haven WooCommerce extension" wordpress plugin just like any other Wordpress plugin.
2. Activate
3. Configure it with your wallet rpc address, (username or password not requested), your Haven address 
4. Enjoy it!

== Remove plugin ==

1. Deactivate plugin through the 'Plugins' menu in WordPress
2. Delete plugin through the 'Plugins' menu in WordPress

== Screenshots == 
1. Monero Payment Box
2. Monero Options

== Changelog ==

soon

== Upgrade Notice ==

soon

== Frequently Asked Questions ==

* What is Haven?
Haven Protocol decentralised algorithmic synthetic asset platform based on the Monero codebase. The idea was for a private coin that anyone could use anywhere with no 3rd parties, with the ability to convert between currencies in your own digital vault. Many described it as the ultimate expression of what a cryptocurrency should be. 

See https://havenprotocol.org/ for more information

* What is a Haven wallet?

A Haven wallet is a piece of software that allows you to create an Haven account, store your funds and interact with the Haven network. You can get a Haven wallet from https://havenprotocol.org/products/

* What is haven-wallet-rpc ?
The haven-wallet-rpc is an RPC server that will allow this plugin to communicate with the Haven network. You can download it from https://getmonero.org/downloads with the command-line tools.

* Why do I see `[ERROR] Failed to connect to haven-wallet-rpc at localhost port 18080
Syntax error: Invalid response data structure: Request id: 1 is different from Response id: ` ?
This is most likely because this plugin can not reach your haven-wallet-rpc. Make sure that you have supplied the correct host IP and port to the plugin in their fields. If your haven-wallet-rpc is on a different server than your wordpress site, make sure that the appropriate port is open with port forwarding enabled.

* How can I get support?
Support is available in the support channel of Haven Discord: https://discordapp.com/invite/CCtNxfG