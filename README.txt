CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration


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


CONFIGURATION
-------------

* Configure settings in Administration » Commerce » Point of Sale.
