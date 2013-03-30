<?php

/**
 * Menu callback for admin/commerce/pos.
 *
 * This is the main entry point into the CommercePOS interface.
 */
function commerce_pos_pos_page() {
  $pos = CommercePOS::instance();

  // Attempt to process a command if it has been entered as a $_GET variable.
  if (!empty($_GET['command'])) {
    // All commands entered this way need to have a token for security reasons.
    if (empty($_GET['token']) || !drupal_valid_token($_GET['token'], 'pos_command')) {
      drupal_set_message('Invalid token.', 'error');
    }
    else {
      try {
        $pos->executeCommand($_GET['command']);
      }
      catch(InvalidArgumentException $e) {
        form_set_error('input', $e->getMessage());
      }
      catch(RuntimeException $e) {
        watchdog_exception('POS Input', $e);
        drupal_set_message(t('There was an unspecified error.'), 'error');
      }
      drupal_goto('admin/commerce/pos');
    }
  }
  // Display the interface.
  return POS_Interface::instance($pos)->build();
}

/**
 * Callback for commands that implement the POS_Command_Modal class.
 *
 * @param $js
 *  TRUE|FALSE for whether the call is being made
 *
 * @param $command_id
 *  The ID of the command to use.
 *
 * @return array|mixed|void
 *  The output, if
 */
function commerce_pos_modal_command_window_page($js, $button_id) {
  $pos = CommercePOS::instance();
  $interface = POS_Interface::instance($pos);

  $button = $interface->getButton($button_id);
  if ($button && $button instanceof POS_Button_Modal) {
    if ($button->access($pos, NULL)) {
      ctools_include('ajax');
      ctools_include('modal');
      drupal_set_title($button->getName());

      $args = func_get_args();
      unset($args[1]);
      array_unshift($args, $pos);
      $output = call_user_func_array(array($button, 'modalPage'), $args);

      if ($js) {
        if (!$output) {
          $output = $interface->buildAjax();
          $output[] = ctools_modal_command_dismiss();
        }
        print ajax_deliver(array(
          '#type' => 'ajax',
          '#commands' => $output,
        ));
        drupal_exit();
      }
      return $output;
    }
    else {
      return drupal_access_denied();
    }
  }
  else {
    return drupal_not_found();
  }
}

/**
 * Administrator configuration form.
 */
function commerce_pos_configuration_form($form, $form_state) {

  $header = variable_get('commerce_pos_receipt_header', array('value' => NULL, 'format' => NULL));
  $form['commerce_pos_receipt_header'] = array(
    '#type' => 'text_format',
    '#title' => t('Receipt Header'),
    '#default_value' => $header['value'],
    '#format' => $header['format'],
  );
  $footer = variable_get('commerce_pos_receipt_footer', array('value' => NULL, 'format' => NULL));
  $form['commerce_pos_receipt_footer'] = array(
    '#type' => 'text_format',
    '#title' => t('Receipt Footer'),
    '#default_value' => $footer['value'],
    '#format' => $footer['format'],
  );
  $form['commerce_pos_touchscreen_mode'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable Touchscreen Mode'),
    '#description' => t('Displays the keypad and submit button for easier touch-screen use.'),
    '#default_value' => variable_get('commerce_pos_touchscreen_mode', FALSE)
  );
  if(module_exists('barcode')) {
    $options = array();
    module_load_include('inc', 'barcode', 'includes/barcode.plugins');
    $barcode_plugins = barcode_discover_plugins();
    foreach($barcode_plugins as $plugin_group) {
      $options += $plugin_group;
    }
    $form['commerce_pos_barcode_format'] = array(
      '#type' => 'select',
      '#title' => t('Barcode Format'),
      '#default_value' => variable_get('commerce_pos_barcode_format', 'EAN-13'),
      '#options' => $options,
      '#description' => t('Set the format of barcodes printed on receipts.'),
    );
  }

  $form['#submit'] = array('commerce_pos_configuration_form_submit');

  return system_settings_form($form);
}

/**
 * Submit handler for the administration configuration form.
 *
 * system_settings_form() does the dirty work, we just have to clear
 * the plugin caches so any settings can be read in.
 */
function commerce_pos_configuration_form_submit($form, $form_state) {
  ctools_include('plugins');
  foreach(array('commands', 'panes') as $plugin_type) {
    $info = ctools_plugin_get_info('commerce_pos', $plugin_type);
    if($info['cache']) {
      cache_clear_all("plugins:commerce_pos:$plugin_type", $info['cache table']);
    }
  }
}