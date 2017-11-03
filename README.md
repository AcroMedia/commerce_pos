[![Build Status](https://travis-ci.org/AcroMedia/commerce_pos.svg?branch=7.x-2.x)](https://travis-ci.org/AcroMedia/commerce_pos)

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Contributing


INTRODUCTION
------------

Provides a Point of Sale interface for Drupal Commerce, allowing
administrators/employees to log in and process payments and returns via an AJAX
form interface.


REQUIREMENTS
------------

This module requires the following modules:

 * Drupal Commerce (https://drupal.org/project/commerce)
 * Rules (https://drupal.org/project/rules)
 * Views (https://drupal.org/project/views)
 * Commerce Custom Offline Payments (https://www.drupal.org/project/commerce_cop)
 * Date (https://www.drupal.org/project/date)
 * jQuery Update (https://www.drupal.org/project/jquery_update) running jQuery
   1.7 or higher.


RECOMMENDED MODULES
-------------------

 * To use key bindings on certain buttons, you will need to install the Form
   API Keybinds module: https://www.drupal.org/project/form_keybinds
 * Commerce Message is required if you want to use the Message submodule:
   https://drupal.org/project/commerce_message
 * Commerce Stock is required if you want to use the stock submodule:
   https://drupal.org/project/commerce_stock
 * Commerce Discount is required if you want to use the discount submodule:
   https://www.drupal.org/project/commerce_discount


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.
 * To print receipts using the Receipt submodule, you must download the jQuery
   Print plugin from https://github.com/DoersGuild/jQuery.print and place the
   jquery.print.js file into sites/all/libraries/jquery-print
 * To generate barcodes on labels, the Picqer php barcode generation library must be used, please install 
   with composer using composer manager. 
   * Composer Manager: https://www.drupal.org/node/2405805


CONFIGURATION
-------------

  * Configure settings in Administration » Point of Sale » Settings. For
  Commerce Kickstart installations, go to Store Settings » Point of Sale »
  Settings.
  * Search API Index: If your site has any API indexes installed they will be
  available here. You can use your API index or Drupal's default search to
  query the database for products.
    * Extra note: If you decide to use Search API, the index you choose must be
    based on the commerce_product entity, as it uses the IDs to grab the product.
  * Select which of the available products you would like to integrate with
  Commerce Point of Sale.
  * For each selected product you may choose an image field (product must
  have and image field in its manage fields page). The image field selected
  will be used when displaying the product on the Point of Sale page.
  * To configure payment methods navigate to Administration » Custom Offline Payments.
  For Commerce Kickstart installations, go to Store Settings » Custom Offline Payments.
  * To enable the default payment methods enable the Commerce POS Payments module.


CONTRIBUTING
-------------

## Development

Please use the Drupal.org issue queue to submit all issues and feature requests.

If you would like to contribute to the development of the module, you will need 
to create a fork of the repository on Github and submit a pull request. Please 
link to your pull request in the corresponding issue on Drupal.org and set the 
status to "Needs Review" like you normally would. 

If you submit a patch, you will be asked to create and link to a pull request
instead.

The repository can be found at: https://github.com/AcroMedia/commerce_pos 

## SASS
If you are contributing to this module and need to make changes to the Sass,
you will need to make sure that you have Node.js installed and run:

```npm install```

You can now run the following commands from the command line:

 * ```npm start``` - This will watch the Sass files for changes and compile as needed.
 * ```npm run build``` - This will compile all Sass files to CSS.
