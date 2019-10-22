Midtrans Drupal 8 Commerce Payment Gateway Module
=======================================================

Midtrans :heart: Drupal 8! 
Let your Drupal Commerce 2 store integrated with Midtrans payment gateway.

### Description
This is the official Midtrans extension for the Drupal Commerce 2 E-commerce platform.

### Requirements
The following plugin is tested under following environment:

* PHP v5.6.x or greater
* MySQL version 5.0 or greater
* Drupal v8.x
* [Drupal Commerce 8.x-2.xx ](http://www.drupal.org/project/commerce)

### Installation

#### 1.a Composer Installation

If you are using [Composer](https://getcomposer.org), you can download and install via composer CLI:
```
composer require drupal/commerce-midtrans
```
#### 1.b Manual Instalation

If you are not using Composer, please use [this version.](https://github.com/Midtrans/Midtrans-Drupal8/tree/with-builtin-library) 

### Plugin Configuration
1. Open drupal admin page, open menu **Extend**.
2. Look for **Commerce Midtrans** modules under COMMERCE (CONTRIB) group, enable by ticking the checkboxes.
3. Scroll down and click **Install**.
4. Go to **Commerce > Configuration > Payment > Payment gateways**.
5. Look for **Midtrans** and click it.
6. Click **Add payment gateway**, click **Midtrans** under Plugin Actions.
7. Fill the following config fields as instructed on each settings description.
8. Click save.
9. Now Snap Midtrans should appear as a payment options when your customer checkout.

### Midtrans Map Configuration
1. Go to **Settings > Configuration**.
2. Insert ``http://[your web]/payment/notify/midtrans`` as your Payment Notification URL in your MAP.
3. Insert ``http://[your web]`` link as Finish/Unfinish/Error Redirect URL in your MAP configuration.

### Get help
* [Midtrans sandbox login](https://dashboard.sandbox.midtrans.com)
* [Midtrans production login](https://dashboard.midtrans.com)
* [Midtrans registration](https://account.midtrans.com/register)
* [Midtrans documentation](http://docs.midtrans.com)
* Technical support [support@midtrans.com](mailto:support@midtrans.com)
