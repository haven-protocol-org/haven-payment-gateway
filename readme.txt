=== Monero WooCommerce Extension ===
Contributors: SerHack, mosu-forge
Donate link: http://monerointegrations.com/donate.html
Tags: monero, woocommerce, integration, payment, merchant, cryptocurrency, accept monero, monero woocommerce
Requires at least: 4.0
Tested up to: 5.0.1
Stable tag: trunk
License: MIT license
License URI: https://github.com/monero-integrations/monerowp/blob/master/LICENSE
 
Monero WooCommerce Extension is a Wordpress plugin that allows to accept monero at WooCommerce-powered online stores.

== Description ==

Your online store must use WooCommerce platform (free wordpress plugin).
Once you installed and activated WooCommerce, you may install and activate Monero WooCommerce Extension.

= Benefits =

* Accept payment directly into your personal Monero wallet.
* Accept payment in monero for physical and digital downloadable products.
* Add monero payments option to your existing online store with alternative main currency.
* Flexible exchange rate calculations fully managed via administrative settings.
* Zero fees and no commissions for monero payments processing from any third party.
* Automatic conversion to Monero via realtime exchange rate feed and calculations.
* Ability to set exchange rate calculation multiplier to compensate for any possible losses due to bank conversions and funds transfer fees.

== Installation ==

1. Install "Monero WooCommerce extension" wordpress plugin just like any other Wordpress plugin.
2. Activate
3. Configure it with your wallet rpc address, (username or password not requested), your monero address 
4. Enjoy it!

== Remove plugin ==

1. Deactivate plugin through the 'Plugins' menu in WordPress
2. Delete plugin through the 'Plugins' menu in WordPress

== Screenshots == 
1. Monero Payment Box
2. Monero Options

== Changelog ==

= 0.1 =
* First version ! Yay!

= 1.0 =
* Added the view key option

= 2.1 =
* Verify transactions without haven-wallet-rpc
* Optionally accept zero confirmation transactions
* bug fixing

= 2.2 =
* Fix some bugs

= 2.3 =
* Bug fixing

= 3.0.0 =
Huge shoutout to mosu-forge who contributes a lot to make 3.0 possible.
* Ability to set number of confirms: 0 for zero conf, up to 60.
* Amount owed in XHV gets locked in after the order for a configurable amount of time after which the order is invalid, default 60 minutes.
* Shows transactions received along with the number of confirms right on the order success page, auto-updates through AJAX.
* QR code generation is done with Javascript instead of sending payment details to a 3rd party.
* Admin page for showing all transactions made to the wallet.
* Logic is done via cron, instead of the user having to stay on the order page until payment is confirmed.
* Payment details (along with the txid) are always visible on the customer's account dashboard on the my orders section.
* Live prices are also run via cron, shortcodes for showing exchange rates.
* Properly hooks into order confirmation email page.


== Upgrade Notice ==

soon

== Frequently Asked Questions ==

* What is Monero ?
Monero is completely private, cryptographically secure, digital cash used across the globe. See https://getmonero.org for more information

* What is a Monero wallet?
A Monero wallet is a piece of software that allows you to store your funds and interact with the Monero network. You can get a Monero wallet from https://getmonero.org/downloads

* What is haven-wallet-rpc ?
The haven-wallet-rpc is an RPC server that will allow this plugin to communicate with the Monero network. You can download it from https://getmonero.org/downloads with the command-line tools.

* Why do I see `[ERROR] Failed to connect to haven-wallet-rpc at localhost port 18080
Syntax error: Invalid response data structure: Request id: 1 is different from Response id: ` ?
This is most likely because this plugin can not reach your haven-wallet-rpc. Make sure that you have supplied the correct host IP and port to the plugin in their fields. If your haven-wallet-rpc is on a different server than your wordpress site, make sure that the appropriate port is open with port forwarding enabled.
