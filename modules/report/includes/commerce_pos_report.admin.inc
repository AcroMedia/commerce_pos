<?php

/**
 * @file
 * Admin page callbacks for Commerce POS Report.
 */

/**
 * Callback for the POS report settings form.
 */
function commerce_pos_report_settings($form, &$form_state) {
  $hour_options = array();
  $minute_options = array();

  for ($i = 0; $i <= 23; $i++) {
    $hour_options[$i] = $i;
  }

  for ($i = 1; $i <= 59; $i++) {
    $minute_options[$i] = $i;
  }

  $form['end_of_day'] = array(
    '#type' => 'fieldset',
    '#title' => t('End of Day'),
    '#description' => t('Select the end of day time. After this time, reports will no longer be able to be updated.'),
  );

  $form['end_of_day']['commerce_pos_report_end_of_day_hour'] = array(
    '#type' => 'select',
    '#title' => t('Hour'),
    '#options' => $hour_options,
    '#default_value' => variable_get('commerce_pos_report_end_of_day_hour', 23),
  );

  $form['end_of_day']['commerce_pos_report_end_of_day_minute'] = array(
    '#type' => 'select',
    '#title' => t('Minute'),
    '#options' => $minute_options,
    '#default_value' => variable_get('commerce_pos_report_end_of_day_minute', 59),
  );

  return system_settings_form($form);
}
