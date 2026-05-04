=== AA - Findomestic Simulator ===
Contributors: alessioangeloro
Tags: woocommerce, findomestic, installment, financing, simulator
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Findomestic installment simulator for WooCommerce. Displays a button on the product page that opens a modal with the financing simulation.

== Description ==

**AA - Findomestic Simulator** integrates the Findomestic installment simulator into WooCommerce. On every product page, a configurable button appears that, when clicked, opens a modal showing the available installment plans: duration, monthly amount, TAN and TAEG calculated on the product price.

AA - Findomestic Simulator is designed for Italian merchants who want to show their customers the option to finance purchases with Findomestic, even before completing the order.

= Features =

* **"Simulate Findomestic installments"** button on every product page (label is customizable)
* Responsive modal with a table of available installment plans
* Simulated amount based on the actual product price, including variants for variable products
* Configurable Findomestic disclaimer, required for financing communications
* Compatible with simple and variable products
* Minimum simulation amount: **€1,000**, as per Findomestic policy
* Auto-deactivation if the Pro version is active

= Findomestic Credentials =

To use the simulator, you need the **TVEI** and **PRF** codes provided by Findomestic to the merchant. Without these credentials, the simulator cannot call the Findomestic API.

= Pro Version =

For merchants who also want to integrate Findomestic as a payment method at checkout, with full order management, redirect and callback handling for the financing application outcome, the Pro version **AA - Findomestic for WooCommerce** is available.

The Pro version is distributed at:
[https://alessioangeloro.it/prodotto/findomestic-per-woocommerce/](https://alessioangeloro.it/prodotto/findomestic-per-woocommerce/)

If you install the Pro version while the Lite is active, **AA - Findomestic Simulator** automatically deactivates itself to avoid hook and AJAX duplication.

== Installation ==

1. Upload the `aa-findomestic-simulator` folder to `/wp-content/plugins/` or install the plugin directly from the WordPress Plugin screen.
2. Activate the plugin from the WordPress Plugin screen.
3. Go to **Settings > Findomestic Simulator**.
4. Enter the **TVEI** and **PRF** codes provided by Findomestic.
5. Check **"Enable installment simulator"**.
6. Optionally customize the button label and the Findomestic disclaimer.
7. Save. The button will appear on every WooCommerce product page.

== Frequently Asked Questions ==

= Can I use the plugin without Findomestic credentials? =

No. The **TVEI** and **PRF** credentials are required to call the Findomestic simulation API. You need to request them from your Findomestic representative.

= Does the plugin handle payment at checkout? =

No. **AA - Findomestic Simulator** only handles the installment simulation on the product page. To integrate Findomestic as a payment method at checkout, the Pro version is required.

= Does it work with variable products? =

Yes. For variable products, the button becomes active after all variants have been selected. If the user clicks before selecting the variants, a message is shown indicating which variant is missing.

= What is the €1,000 minimum amount? =

It is the minimum threshold required by Findomestic to simulate financing. For products below **€1,000**, the button is not displayed.

= Where is the data sent? =

Simulation data — product amount and merchant TVEI/PRF codes — is sent to Findomestic servers to calculate installment plans. See the **External Services** section for details.

== External Services ==

AA - Findomestic Simulator connects to Findomestic servers to retrieve installment simulation data. Without this call, the installment modal cannot function.

**Service:** Findomestic Banca S.p.A. – Ecommerce installment simulation API

= Endpoints called =

* `https://secure.findomestic.it/clienti/webapp/ecommerce/` — initial session fetch
* `https://secure.findomestic.it/b2c/ecm/v1/order/create` — simulation order creation
* `https://secure.findomestic.it/b2c/ecm/v1/order?token={token}` — order data retrieval
* `https://secure.findomestic.it/b2c/ecm/v1/order/{order_id}/offer` — installment offers request

= When the call is made =

The call is triggered when a site visitor clicks the **"Simulate Findomestic installments"** button on the product page.

= Data sent =

* Product amount, in euros
* Merchant TVEI code, configured by the admin in the settings
* Merchant PRF code, configured by the admin in the settings
* Standard HTTP headers such as User-Agent, Origin and Referer

= Personal data =

No personal visitor data is sent to Findomestic during the simulation. Only the product amount and merchant credentials are transmitted.

= Useful links =

* [Findomestic Terms of Service](https://www.findomestic.it/)
* [Findomestic Privacy Policy](https://www.findomestic.it/privacy)

== Screenshots ==

1. Plugin settings page
2. Simulator button on the product page
3. Modal with the installment table

== Changelog ==

= 1.0.3 =
* First public release of the Findomestic installment rate simulator for WooCommerce.
* Added support for simple and variable products.
* Added settings page with TVEI, PRF, button label and disclaimer fields.
* Added auto-deactivation when the Pro version is active.

== Upgrade Notice ==

= 1.0.3 =
First public release.
