<?php

/**
 * @file
 * Example usage and documentation for the commerce_pos_terminal API.
 */

/**
 * Provide information about terminal server plugins.
 *
 * @return array
 *   Keyed by the machine name of the service. Values are information about the
 *   service.
 *   - name: The machine name of the service.
 *   - label: The label for the service.
 *   - class: The name of the class, which must implement
 *   CommercePosTerminalServiceInterface.
 *   - file: Optional, a file to include before instantiating the class.
 *   - configure: Optional, a path to the service's configuration form.
 */
function hook_commerce_pos_terminal_service_info() {
  $server['commerce_pos_terminal'] = array(
    'name' => 'commerce_pos_terminal',
    'label' => t('Default Terminal Service'),
    'class' => 'CommercePosTerminalService',
    'file' => drupal_get_path('module', 'commerce_pos_terminal') . '/classes/CommercePosTerminal.php',
    'configure' => 'admin/commerce/config/pos/terminal/terminal_example',
  );

  return $server;
}
