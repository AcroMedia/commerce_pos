#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Ensure the right Drupal version is installed.
# The first time this is run, it will install Drupal.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

# Change to the Drupal directory
cd "$DRUPAL_TI_DRUPAL_DIR"

# Create the the module directory (only necessary for D7)
# For D7, this is sites/default/modules
# For D8, this is modules
mkdir -p "$DRUPAL_TI_DRUPAL_DIR/$DRUPAL_TI_MODULES_PATH"

drush en -y commerce_pos_cashier commerce_pos_discount commerce_pos_keypad commerce_pos_location
drush en -y commerce_pos_messages commerce_pos_payment commerce_pos_receipt commerce_pos_register
drush en -y commerce_pos_report commerce_pos_stock commerce_pos_terminal commerce_pos_upc_scan
