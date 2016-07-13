<?php
/**
 * @file
 * Administrative forms for commerce_pos_terminal.
 */

/**
 * Settings for commerce_pos_terminal.
 * The administrator can:
 * - Select which terminal service to use.
 * - Map the payment types provided by that service to payment methods known by
 * commerce_pos.
 * @param $form
 * @param $form_state
 * @return array
 */
function commerce_pos_terminal_settings_form($form, &$form_state) {
  $form = array();

  if (empty($form_state['services'])) {
    $form_state['services'] = commerce_pos_terminal_services_info();
  }

  // The service we care about is either the one selected in the form or the one
  // saved in configuration.
  if (isset($form_state['values']['service'])) {
    $current_service = $form_state['services'][$form_state['values']['service']];
  }
  else {
    $current_service = variable_get('commerce_pos_terminal_service');
  }

  $form['services'] = array(
    '#type' => 'fieldset',
    '#title' => t('Terminal Service Plugins'),
    '#id' => 'commerce-pos-terminal-services',
  );

  if (!empty($form_state['services'])) {
    $form['services']['service'] = array(
      '#type' => 'radios',
      '#title' => t('Terminal Service Plugin'),
      '#description' => t('Select the terminal service plugin to use.'),
      '#options' => commerce_pos_terminal_settings_service_options($form_state['services']),
      '#default_value' => (isset($current_service['name'])) ? $current_service['name'] : NULL,
      '#ajax' => array(
        'wrapper' => 'commerce-pos-terminal-services',
        'callback' => 'commerce_pos_terminal_settings_form_service_callback',
      ),
    );

    // When a service is selected, ask for the map of payment types to methods.
    if ($current_service) {
      if ($class = commerce_pos_terminal_get_service_class($current_service)) {
        $form['services']['payment_types'] = array(
          '#type' => 'fieldset',
          '#title' => t('Payment Types'),
          '#tree' => TRUE,
          '#description' => t('Map the payment types provided by the terminal service to payment methods known by Commerce POS.'),
        );

        if (class_exists($class)) {
          $current_map = variable_get('commerce_pos_terminal_payment_type_map');
          $options = commerce_payment_method_options_list();

          foreach ($class::getPaymentTypes() as $payment_type => $payment_label) {
            $form['services']['payment_types'][$payment_type] = array(
              '#type' => 'select',
              '#title' => $payment_label,
              '#options' => $options,
              '#default_value' => (isset($current_map[$payment_type])) ? $current_map[$payment_type] : NULL,
            );
          }
        }
        else {
          drupal_set_message(t('Unable to load the chosen terminal service.'), 'error');
        }
      }
      else {
        drupal_set_message(t('No payment types were provided by this service.'), 'error');
      }
    }
  }
  else {
    $form['services']['no_service'] = array(
      '#markup' => '<p>' . t('No terminal service plugins are available. You can not integrate with a payment terminal until a module providing a plugin is enabled.') . '</p>',
    );
  }

  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Additional Options'),
  );

  $form['options']['commerce_pos_terminal_use_background_process'] = array(
    '#type' => 'checkbox',
    '#title' => t('Process transactions in background process'),
    '#description' => t('Use Background Process to process transactions. Helps avoid timeouts. Requires the !url module.', array(
      '!url' => l('Background Process', 'https://www.drupal.org/project/background_process'),
    )),
    '#default_value' => variable_get('commerce_pos_terminal_use_background_process'),
    '#disabled' => !module_exists('background_process'),
  );

  $form['options']['commerce_pos_terminal_pending_transaction_ping_timer'] = array(
    '#type' => 'textfield',
    '#title' => t('Pending transaction ping delay'),
    '#description' => t('The number of milliseconds to wait between pings to check on updates to pending payment transactions. A lower value means additional requests but less time to wait between the end of a transaction and an update to the user interface.'),
    '#default_value' => variable_get('commerce_pos_terminal_pending_transaction_ping_timer', 1000),
  );

  $form['options']['commerce_pos_terminal_transaction_cleanup_wait'] = array(
    '#type' => 'textfield',
    '#title' => t('Minimum wait for transaction request cleanup'),
    '#description' => t('The minimum amount of time in seconds to wait before removing a record from the list of pending requests.') .
  '<br>' . t('This value should be longer than the maximum amount of time it could take a person to complete a transaction at the payment terminal or for the request to time out.') . '<br> ' . t('If not set, 15 minutes is used. Use -1 to never clean up.') . '<br>' . t('The cleanup occurs on cron so the actual time to clean up may be different.'),
    '#default_value' => variable_get('commerce_pos_terminal_transaction_cleanup_wait', COMMERCE_POS_TERMINAL_CLEANUP_WAIT_DEFAULT),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Validation callback for commerce_pos_terminal_settings_form().
 * @param $form
 * @param $form_state
 */
function commerce_pos_terminal_settings_form_validate($form, &$form_state) {
  if (isset($form_state['values']['commerce_pos_terminal_pending_transaction_ping_timer'])) {
    $value = $form_state['values']['commerce_pos_terminal_pending_transaction_ping_timer'];
    if (!is_numeric($value) || $value < 1) {
      form_set_error('commerce_pos_terminal_pending_transaction_ping_timer', t('The ping delay must be a positive number.'));
    }
  }
}

/**
 * Submit handler for commerce_pos_terminal_settings_form().
 * @param $form
 * @param $form_state
 */
function commerce_pos_terminal_settings_form_submit($form, &$form_state) {
  if (isset($form_state['values']['service'])) {
    $service = $form_state['services'][$form_state['values']['service']];
    variable_set('commerce_pos_terminal_service', $service);

    if (isset($form_state['values']['payment_types'])) {
      variable_set('commerce_pos_terminal_payment_type_map', $form_state['values']['payment_types']);
    }
  }
  elseif (!empty($form_state['services'])) {
    drupal_set_message('You must select a Terminal Service Plugin to interface with payment terminals from the Point of Sale.', 'warning');
  }

  foreach (array(
    'commerce_pos_terminal_transaction_cleanup_wait',
    'commerce_pos_terminal_pending_transaction_ping_timer',
    'commerce_pos_terminal_use_background_process',
    ) as $setting) {
    if (isset($form_state['values'][$setting])) {
      variable_set($setting, $form_state['values'][$setting]);
    }
  }

}

/**
 * Ajax callback for selecting a terminal service in
 * commerce_pos_terminal_settings_form().
 * @param $form
 * @param $form_state
 * @return array
 */
function commerce_pos_terminal_settings_form_service_callback($form, $form_state) {
  return $form['services'];
}

/**
 * Provide a value for the #options element of a form api element.
 * Provides terminal service options.
 * @param array $services
 * @return array
 */
function commerce_pos_terminal_settings_service_options($services) {
  $options = array();

  foreach ($services as $service => $service_info) {
    $options[$service_info['name']] = $service_info['label'];

    if (isset($service_info['configure'])) {
      $options[$service_info['name']] .= ' (' . l('configure', $service_info['configure']) . ')';
    }
  }

  return $options;
}
