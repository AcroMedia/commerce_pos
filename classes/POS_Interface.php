<?php

class POS_Interface {
  protected $pos;
  protected $panes = array();
  protected $buttons = array();
  protected static $instance;

  /**
   * This is the main entry point for getting an interface.
   *
   * @param POS_State $state
   *   The current context of the system.
   *
   * @return POS_Interface
   *   The POS Interface, configured with the enabled panes.
   */
  public function create(POS $pos) {
    if (!self::$instance) {
      $panes = array();

      ctools_include('plugins');
      $plugins = ctools_get_plugins('commerce_pos', 'panes');
      uasort($plugins, 'ctools_plugin_sort');
      foreach ($plugins as $id => $plugin) {
        if ($handler_class = ctools_plugin_get_class($plugin, 'handler')) {
          $handler = new $handler_class($id, $plugin['name'], $plugin['handler_options']);
          if ($handler instanceof POS_Pane) {
            $panes[$id] = $handler;
          }
        }
      }

      $buttons = module_invoke_all('commerce_pos_buttons', $pos);
      drupal_alter('commerce_pos_buttons', $buttons, $pos);

      self::$instance = new self($pos, $panes, $buttons);
    }
    return self::$instance;
  }

  /**
   * Constructor.
   *
   * @param POS_State $state
   * @param POS_Pane[] $panes
   * @param POS_Button[] $buttons
   */
  public function __construct(POS $pos, array $panes, array $buttons) {
    $this->pos = $pos;
    $this->setButtons($buttons);
    $this->setPanes($panes);
  }

  /**
   * Build a render array representing all panes.
   *
   * @return array
   */
  public function build() {
    $output = array();
    foreach ($this->panes as $pane) {
      $output[$pane->getId()] = $this->buildPane($pane);
    }
    if ($render = $this->pos->getState()->getPrintRender()) {
      $output[] = array(
        '#prefix' => '<div class="element-invisible"><div class="pos-print">',
        '#suffix' => '</div></div>',
        'print' => $render,
      );
    }
    return $output;
  }

  /**
   * Build an ajax commands array suitable for output with ajax_deliver
   *
   * @param null $form_render
   *
   * @return array
   */
  public function buildAjax($form_render = NULL) {
    $commands = array();
    foreach ($this->panes as $pane) {
      if ($pane->getId() == 'input') {
        $output = $this->buildPane($pane, TRUE, $form_render);
      }
      else {
        $output = $this->buildPane($pane, TRUE);
      }
      $commands[] = ajax_command_replace('#' . $output['#pane_id'], drupal_render($output));
    }
    if ($render = $this->pos->getState()->getPrintRender()) {
      $commands[] = array(
        'command' => 'printReceipt',
        'content' => drupal_render($render),
      );
    }
    return $commands;
  }

  /**
   * Build the content for a single pane.
   *
   * @param POS_Pane $pane
   * @param array $prebuild
   *
   * @return array
   */
  public function buildPane(POS_Pane $pane, $js = FALSE, array $prebuild = NULL) {
    $pane_id = 'pos-pane-' . drupal_clean_css_identifier($pane->getId());
    return array(
      '#pane_id' => $pane_id,
      '#prefix' => '<div id="' . $pane_id . '">',
      '#suffix' => '</div>',
      '#attached' => array(
        'js' => array(
          libraries_get_path('jqprint') . '/' . JQPRINT_FILENAME,
          drupal_get_path('module', 'commerce_pos') . '/theme/pos-interface.js',
        ),
        'css' => array(drupal_get_path('module', 'commerce_pos') . '/theme/pos-interface.css'),
        'library' => array(
          array('system', 'jquery.bbq')
        )
      ),
      $prebuild ? $prebuild : $pane->build($this->pos->getState(), $this, $js),
    );
  }

  /**
   * Set the panes of this interface.
   *
   * @param POS_Pane[] $panes
   */
  public function setPanes(array $panes) {
    $this->panes = array();
    foreach ($panes as $pane) {
      $this->panes[$pane->getId()] = $pane;
    }
  }

  /**
   * Get all the panes this interface contains.
   *
   * @return POS_Pane[]
   */
  public function getPanes() {
    return $this->panes;
  }

  /**
   * Get a single pane by ID.
   *
   * @param $id
   *
   * @return POS_Pane|bool
   */
  public function getPane($id) {
    return isset($this->panes[$id]) ? $this->panes[$id] : FALSE;
  }

  /**
   * @param POS_Buttons[] $buttons
   */
  public function setButtons(array $buttons) {
    $this->buttons = array();
    foreach($buttons as $button) {
      $this->buttons[$button->getId()] = $button;
    }
  }

  /**
   * @return POS_Buttons[]
   */
  public function getButtons() {
    return $this->buttons;
  }

  /**
   * @param $id
   *
   * @return POS_Button|bool
   */
  public function getButton($id) {
    return isset($this->buttons[$id]) ? $this->buttons[$id] : FALSE;
  }
}





