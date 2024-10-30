=== KueskiPay Gateway ===
Contributors: Kueski 
Tags: woocommerce, kueski, ecommerce, e-commerce, payment gateway
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 2.3.3
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add Kueski gateway to buy now and pay later on your store.

== Description ==

Choose how many fortnights to pay with Kueski Pay

== Requirements ==

* WordPress 6.0 or newer.
* WooCommerce 7.6 or newer.
* PHP 7.4 or newer is recommended.

== Privacy Notices ==
This plugin connects to a third-party services to perform its functions. Below are the circunstances under wich these connections are made:

1. CDN Service for Promotional Widgets
    - **Service Name:** KueskiPay CDN
    - **Description:** This plugin uses the CDN service at https://cdn.kueskipay.com/ to display promotional widgets on the product and cart pagesin WooCommerce.
    - **Data Sent:** The following data is sent to this service via Get request:
        - **Authorization:** The public key provided at the time of integration.
        - **Integration:** The platform being integrated, in this case, WooCommerce.
        - **Version:** The current version of this plugin.
        - ** Sandbox:** Indicates wheter the current environment is sandbox or production.
    - **Service URL:** https://cdn.kueskipay.com/widgets.js
- **Example URL:** https://cdn.kueskipay.com/widgets.js?authorization=[public_key]&integration=woocommerce&version=[plugin_version]&sandbox[true/false]
- **Files Involved:** 
    - public/class-wc-kuesku-gategay-public.php (Line 227)
- **Terms of Use and Policy:** https://preguntas.frecuentes.kueski.com/hc/es/articles/12385599806747-PRIVACY-NOTICE-FOR-THIRD-PARTIES-AND-COMMERCIAL-ALLIES-OF-KUESKI-SAPI-DE-CV-SOFOM-ENR

2. Payment Order Creation and Management
    - **Servce Name:** KueskiPay Payment API
    - **Description:** This plugin uses the following services to create and manage payment orders:
        - ** Sandbox:** https://woocommerce-middleware-go.staging-pay.kueski.codes/api/v1/order/create
        - ** Production:** https://woocommerce-middleware-go.production-pay.kueski.com/api/v1/order/create
    - **Usage:** The plugin sends the current cart order details to create and order and then redirects the user to the service site to complete their payment.
    - **Data Sent:** The following data is sent to this service:
        - **Order Description**
        - **Order Amounts:** total, shipping, discounts and taxes.
        - **Order Items:** Details of each order item.
        - **Shipping Address:**
        - **Billing Address:**
    - **Files involved:**
        - includes/class-wc-kueski-gateway-api.php (Lines 57, 92, 151, 221)
    - **Therms of Use and Privacy Policy:** https://preguntas.frecuentes.kueski.com/hc/es/articles/12385430001563-Aviso-de-privacidad-integral-para-clientes-y-usuarios-de-Kueski-S-A-P-I-de-C-V-SOFOM-E-N-R
