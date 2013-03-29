<?php


/**
 * Declare POS commands.
 *
 * This function returns a list of POS commands, with the following keys available:
 *
 * - title: The default display title for the command.
 * - input_pattern: The input pattern of this command.
 * - handler: The class name of the handler class (must extend POS_Command)
 * - handler_options: An array of options to pass to the handler class. The keys
 *    and values of this array will depend on the handler class.
 *
 * For altering commands, use CTools plugin alter functions.
 *  @see hook_ctools_plugin_pre_alter()
 *  @see hook_ctools_plugin_post_alter().
 *
 * $plugins is actually processed by the CTools plugin system.  This means that
 * commands can also be declared using CTools plugin files.  This is not recommended
 * except in the case that you need child panes for a given plugin.
 *
 * @see plugins/command/payment.inc
 *
 * @return array
 */
function hook_commerce_pos_commands() {
  $plugins = array();
  $plugins['clear'] = array(
    'title' => 'Clear',
    'handler' => 'POSCommand_Clear',
    'input_pattern' => 'CL',
  );
  $plugins['addproduct'] = array(
    'title' => 'Add Product',
    'handler' => 'POSCommand_AddProduct',
    'weight' => 255,
    'input_pattern' => '%s',
  );
  $plugins['order'] = array(
    'title' => 'Load Order',
    'handler' => 'POSCommand_LoadOrder',
    'input_pattern' => 'OR%s',
  );
  $plugins['reprint'] = array(
    'title' => 'Print Receipt',
    'handler' => 'POSCommand_Reprint',
    'input_pattern' => 'RP',
  );
  $plugins['setuser'] = array(
    'title' => 'Customer',
    'handler' => 'POSCommand_SetUser',
    'input_pattern' => 'US%s',
  );
  $plugins['void'] = array(
    'title' => 'Void',
    'handler' => 'POSCommand_Void',
    'input_pattern' => 'VOID%s',
  );
  return $plugins;
}

/**
 * Declare POS panes.
 *
 * This function returns a list of POS panes provided by the module, with the
 * following keys available:
 *
 * - title: The default display title for the pane.
 * - handler: The class name of the handler class (must extend POS_Pane)
 * - handler_options: An array of options to pass to the handler class. The keys
 *    and values of this array will depend on the handler class.
 *
 * For altering panes, use CTools plugin alter functions.
 *  @see hook_ctools_plugin_pre_alter()
 *  @see hook_ctools_plugin_post_alter().
 *
 * $plugins is actually processed by the CTools plugin system.  This means that
 * panes can also be declared using CTools plugin files.  This is not recommended
 * except in the case that you need child panes for a given plugin.
 *
 * @return array
 */
function hook_commerce_pos_panes() {
  $plugins = array();

  $plugins['alerts'] = array(
    'title' => 'Alerts',
    'handler' => 'POSPane_Alerts',
    'weight' => -5
  );
  $plugins['input'] = array(
    'title' => 'Input',
    'handler' => 'POSPane_Input',
    'weight' => -1,
  );
  $plugins['order'] = array(
    'title' => 'Order',
    'handler' => 'POSPane_Order',
    'weight' => 5,
  );
  $plugins['commands'] = array(
    'title' => 'Commands',
    'handler' => 'POSPane_Commands',
    'handler_options' => array(
      'show_keypad' => variable_get('commerce_pos_touchscreen_mode', FALSE)
    ),
    'weight' => 10,
  );
  return $plugins;
}

/**
 * Declare POS Buttons.
 *
 * This function returns a list of POS buttons provided by a module, with the
 * following keys available:
 *
 * - title: The default display title for the pane.
 * - handler: The class that will handle this button.
 * - handler_options: Additional options to pass to the handler.  Options
 *   depend on the handler that is used.
 *
 * For altering button plugins, use CTools plugin alter functions.
 *  @see hook_ctools_plugin_pre_alter()
 *  @see hook_ctools_plugin_post_alter().
 *
 * For altering button output, you can override theme('link__pos_button')
 */
function hook_commerce_pos_buttons() {
  $buttons = array();

  $buttons['my_view'] = array(
    'title' => t('My View'),
    'handler' => 'POS_Button_Modal_View',
    'handler_options' => array(
      'view' => 'my_view',
      'display' => 'my_display',
    )
  );

  return $buttons;
}