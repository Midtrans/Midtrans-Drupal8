Midtrans Drupal 8/9 Commerce Payment Gateway Module
=======================================================

Midtrans :heart: Drupal 8/9!
Let your Drupal Commerce 2 store integrated with Midtrans payment gateway.

#### Description
This is the official Midtrans module for the Drupal Commerce 2 E-commerce platform.
Also Available on [Drupal Project Module](https://www.drupal.org/project/midtrans_commerce)

#### Live Demo
Want to see Midtrans Drupal payment plugins in action? We have some demo web-stores for Drupal that you can use to try the payment journey directly, click the link below.
* [Midtrans CMS Demo Store](https://docs.midtrans.com/en/snap/with-plugins?id=midtrans-payment-plugin-live-demonstration)

#### Version
2.1.0
(for Drupal v8.x and Drupal v9.x)

#### Requirements
The following module is tested under following environment:

* PHP v5.6.x or greater
* MySQL version 5.0 or greater
* [Drupal v8.x or greater](https://www.drupal.org/project/drupal)
* [Drupal Commerce 8.x-2.xx ](http://www.drupal.org/project/commerce)

#### Composer Installation
If you are using [Composer](https://getcomposer.org), you can install via composer CLI
1. Open terminal
2. Move to your drupal site folder: `cd /[drupal site folder]/`
2. Run: `composer require drupal/midtrans_commerce`

#### Manual Instalation
The manual installation method involves downloading our feature-rich plugin and uploading it to your webserver via your favourite FTP application.

1. [Download](https://github.com/midtrans/midtrans-drupal8/archive/master.zip) the plugin file to your computer and unzip it, rename folder to ``midtrans_commerce``.
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your Drupal modules installation's ``[Drupal folder]/modules/contrib/`` directory.

#### Plugin Configuration
1. Open drupal admin page, open menu **Extend**.
2. Look for **Commerce Midtrans** modules under COMMERCE (CONTRIB) group, enable by ticking the checkboxes.
3. Scroll down and click **Install**.
4. Go to **Commerce > Configuration > Payment > Payment gateways**.
5. Look for **Midtrans** and click it.
6. Click **Add payment gateway**, click **Midtrans** under Plugin Actions.
7. Fill the following config fields as instructed on each settings description.
8. Click save.
9. Now Snap Midtrans should appear as a payment options when your customer checkout.

#### Midtrans Map Configuration
1. Go to **Settings > Configuration**.
2. Insert ``http://[your web]/payment/notify/midtrans`` as your Payment Notification URL in your MAP.
3. Insert ``http://[your web]/payment/finish/midtrans`` link as Finish/Unfinish/Error Redirect URL in your MAP configuration.

#### Advanced Usage
<details>
<summary>Note on Customer Phone Number</summary>

##### Note on Customer Phone Number
Unfortunately Drupal Commerce by default doesn't have `phone number` as customer data <sup>\[1\]</sup>, so there will be no `phone` data passed to Midtrans side.

If you have modified your Drupal Commerce site to have phone number input field, you may want to customize/edit this payment module to also send `phone` data to Midtrans side.

You can do so by editing these line of codes in this file `/src/PluginForm/MidtransForm.php`:
- https://github.com/Midtrans/Midtrans-Drupal8/blob/12c1e4b06f1adebc8bf8af6b7e531c8d7cfde24f/src/PluginForm/MidtransForm.php#L173-L181

You can uncomment this line:
```php
//'phone' => ,
```
Then modify it to something like this:
```php
'phone' => myCustomFunctionToGetCustomerPhone(),
```
But you will need to figure out on your own, how to programmatically retrieve customer `phone` number based on your site implementation.

You can also add more custom Snap API payload to add more data related to the transaction. Learn more on the API payload [on Snap API docs](http://snap-docs.midtrans.com)

> <sup>\[1\]</sup> At this time of writing, based on `[DrupalCommerceFolder]/modules/contrib/address/src/Plugin/Field/FieldType/AddressItem.php`, the class doesn't have any phone attribute by default.
>
> And no explanation of it on the [Drupal Commerce PG module development guide](https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway/on-site-gateways/stored-payment-methods)

</details>

#### Get help
* Please follow [this step by step guide](https://docs.midtrans.com/en/snap/with-plugins?id=plugin-configuration) for complete configuration. If you have any feedback or request, please [do let us know here](https://docs.midtrans.com/en/snap/with-plugins?id=feedback-and-request).
* [Midtrans sandbox login](https://dashboard.sandbox.midtrans.com)
* [Midtrans production login](https://dashboard.midtrans.com)
* [Midtrans registration](https://account.midtrans.com/register)
* [Midtrans documentation](http://docs.midtrans.com)
* Technical support [support@midtrans.com](mailto:support@midtrans.com)
