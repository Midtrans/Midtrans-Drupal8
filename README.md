Midtrans Drupal 8 Commerce Payment Gateway Module
=======================================================

Midtrans :heart: Drupal 8! 
Let your Drupal Commerce 2 store integrated with Midtrans payment gateway.

### Description
This is the official Midtrans extension for the Drupal Commerce 2 E-commerce platform.

### Version
8.x-2.11 
(for Drupal v 8.x)

### Requirements
The following plugin is tested under following environment:

* PHP v5.6.x or greater
* MySQL version 5.0 or greater
* Drupal v8.x
* [Drupal Commerce 8.x-2.11 ](http://www.drupal.org/project/commerce)

#### Installation Process
The manual installation method involves downloading our feature-rich plugin and uploading it to your webserver via your favourite FTP application.

1. Download the plugin file to your computer and unzip it, rename folder to ``commerce_midtrans``
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your Drupal modules installation's ``[Drupal folder]/modules/contrib/`` directory. 
3. Add veritrans library to the root composer.json file
4. Add this require line to your `composer.json` file:
```json
{
	"require": {
		"veritrans/veritrans-php": "dev-master"
	}
}
```
5. After save the composer.json file, run `composer update` from the same directory as the composer.json file.

#### Plugin Configuration
1. Open drupal admin page, open menu **Extend**.
2. Look for **Commerce Midtrans** modules under COMMERCE (CONTRIB) group, enable by ticking the checkboxes.
3. Scroll down and click **Install**
4. Go to **Commerce > Configuration > Payment > Payment gateways**
5. Look for **Midtrans** and click it
6. Click **Add payment gateway**, click **Midtrans (Online Payment via Midtrans)** under Plugin Actions
7. Fill the following config fields as instructed on each settings description
8. Click save.
9. Now Snap Midtrans should appear as a payment options when your customer checkout.

#### Midtrans Map Configuration
1. Go to **Settings > Configuration**
2. Insert ``http://[your web]/payment/notify/midtrans`` as your Payment Notification URL in your MAP
3. Insert ``http://[your web]`` link as Finish/Unfinish/Error Redirect URL in your MAP configuration.

#### Get help
* [Midtrans sandbox login](https://dashboard.sandbox.midtrans.com)
* [Midtrans production login](https://dashboard.midtrans.com)
* [Midtrans registration](https://account.midtrans.com/register)
* [Midtrans documentation](http://docs.midtrans.com)
* Technical support [support@midtrans.com](mailto:support@midtrans.com)