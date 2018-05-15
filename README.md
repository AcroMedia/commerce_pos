CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Usage
 * Maintainers

INTRODUCTION
------------

The Commerce Point of Sale (POS) module provides a Point of Sale interface for
Drupal Commerce, allowing in-person transactions via cash or card, returns,
multiple registers and locations and EOD reporting, along with the ability to
add or remove unique cashiers easily. All integrated with Commerce to allow you
to use the same products, customers and orders between both systems.

The ease of use allows for quick training and the intuitive build eliminates
many common user errors.

The Point of Sales also makes the use of tablets, laptops or desktops fully
compatible and interactive with each other.

 * For a full description of the module visit:
   https://www.drupal.org/project/commerce_pos

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/commerce_pos

REQUIREMENTS
------------

This module requires the following modules:

 * Commerce (https://drupal.org/project/commerce)
 * Search API (https://drupal.org/project/search_api)

### Commerce POS Receipt

To print receipts, the jQuery.print javascript plugin is required, see the
Commerce POS Receipt module's README for install instructions.

INSTALLATION
------------

 It is recommended to install the Commerce POS module via composer. 
 Both Drupal Commerce and Commerce POS have a number of composer based requirements
 and are easiest to install with composer.

`composer require drupal/commerce_pos`

CONFIGURATION
-------------

### Order Types

Order types must have field_cashier and field_register to be compatible with
the POS. The default order type has these automatically, but if you wish to
include any other types you will need to add these fields. Any order types
without these fields will not show in order lookup and will not load in the
POS interface.

### Search

Commerce POS uses Search API for product search,
since it has to do lots of searching and it has to do it fast.

* Set the server and index in the Commerce POS settings.
* Search index should be of product_variations.
* Search index must index the commerce_store field for store filtering.

The config/optional has example setups for server and index,
based on the search_api_db server. If you enable the search_api_db module these
will be installed automatically.

### UPC

If you wish to use UPC fields, you'll want to add the UPC field to your form
config. It is added to the default product variation type by default but not
displayed. If you wish to add it to any other product variations, just add it
as you would any other field.

USAGE
------------

1. Navigate to Administration > Extend and enable the module.
2. After a store and register has been created, navigate to Administration >
   Commerce > Point of Sale.
3. Enter a product name in the "Order Items" field.
4. Select the adjustments: Custom, Fee, Promotion, or Tax. Enter label and
   amount. Select whether this is included in the base price.
5. Choose the customer and enter in the contact email address.
6. Select Payments and Completion to save.

MAINTAINERS
-----------

 * Shawn McCabe (smccabe) - https://www.drupal.org/u/smccabe
 * Alex Pott (alexpott) - https://www.drupal.org/u/alexpott

Supporting organizations:

 * Acro Media Inc - https://www.drupal.org/acro-media-inc
