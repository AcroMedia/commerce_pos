<?php


/**
 * Implements drush_MODULE_post_COMMAND().
 *
 * Download jqprint and put it in sites/all/libraries.
 */
function drush_commerce_pos_post_pm_enable() {
  $extensions = func_get_args();
  // Deal with comma delimited extension list.
  if (strpos($extensions[0], ',') !== FALSE) {
    $extensions = explode(',', $extensions[0]);
  }

  if (in_array('commerce_pos', $extensions) && !drush_get_option('skip')) {
    $path = drush_get_context('DRUSH_DRUPAL_ROOT');
    if($library_path = module_invoke('libraries', 'get_path', 'jqprint')) {
      $path.= '/'  . $library_path;
    }
    else {
      $path .= '/sites/all/libraries/jqprint';
    }

    if (is_dir($path) && file_exists($path . '/' . JQPRINT_FILENAME)) {
      drush_log('jqprint already present. No download required.', 'ok');
    }
    else {
      if(file_prepare_directory(dirname($path), FILE_CREATE_DIRECTORY|FILE_MODIFY_PERMISSIONS)) {
        $request = drupal_http_request(JQPRINT_DOWNLOAD);
        if($request->code == 200 && !empty($request->data)) {
          $folder_path = drush_tempdir();
          $filename = pathinfo(JQPRINT_DOWNLOAD, PATHINFO_FILENAME);
          file_put_contents("$folder_path/$filename", $request->data);
          drush_tarball_extract("$folder_path/$filename", $folder_path);
          if(drush_move_dir($folder_path .'/jquery.jqprint-master', $path, TRUE)) {
            drush_log('jqprint jquery plugin downloaded to ' . $path, 'ok');
            return;
          }
        }
      }
      $message = dt('jQuery jqprint plugin download failed.  Please download the plugin from !link and move the plugin file (!script) to @path', array(
        '!script' => JQPRINT_FILENAME,
        '!link' => JQPRINT_DOWNLOAD,
        '@path' => $path . '/' . JQPRINT_FILENAME,
      ));
      drush_log($message, 'warning');
    }
  }
}