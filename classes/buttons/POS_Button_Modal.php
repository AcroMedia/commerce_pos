<?php
/**
 * @file
 *  This class represents any button that opens a modal window.
 */
abstract class POS_Button_Modal implements POS_Button {
  protected $config = array();
  protected $id;
  protected $name;

  public function __construct($name, $id, array $options = array()) {
    $this->name = $name;
    $this->id = $id;
    $this->config = $options + $this->config;
  }

  public function getName() {
    return $this->name;
  }

  public function getId() {
    return $this->id;
  }

  public function render(CommercePOS $pos, $text = NULL, $input = NULL, $options = array()) {
    //@todo: Implement.
    ctools_include('ajax');
    ctools_include('modal');
    ctools_modal_add_js();

    return theme('link__pos_button__modal', array(
      'text' => $this->name,
      'path' => $this->getModalUrl(),
      'options' => array(
        'attributes' => array(
          'class' => array('pos-button', 'ctools-use-modal'),
        ),
        'html' => FALSE,
      )
    ));
  }

  function getModalUrl() {
    return 'admin/commerce/pos/nojs/' . $this->id;
  }

  /**
   * This method is invoked when the user clicks on the button.
   *
   * If $js is true, it should return an array of ajax framework commands
   * that set content to the modal window.
   *
   * If $js is false, it should return a render array, or rendered markup.
   *
   * @param CommercePOS $pos
   *  The POS object.
   * @param bool $js
   *  Indicates whether the page is being viewed in a modal window or separately.
   *
   * @return mixed
   */
  abstract function modalPage(CommercePOS $pos, $js);
}