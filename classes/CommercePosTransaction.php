<?php

/**
 * @file
 * PosTransaction class definition.
 */

class CommercePosTransaction {

  const TABLE_NAME = 'commerce_pos_transaction';

  public $transactionId = 0;
  public $uid = 0;
  public $cashier = 0;
  public $orderId = 0;
  public $type = '';
  public $registerId = 0;
  public $data = array();
  public $created = 0;
  public $changed = 0;
  public $completed = 0;

  protected $bases = array();
  protected $order = FALSE;
  protected $orderWrapper = FALSE;
  protected $actions = array();
  protected $events = array();

  /**
   * Constructor.
   *
   * @param $transaction_id
   * @param $type
   * @param $uid
   * @param $cashier
   *
   * @throws \Exception
   */
  public function __construct($transaction_id = NULL, $type = NULL, $uid = NULL, $cashier = NULL) {
    if ($transaction_id !== NULL && $type == NULL && $uid == NULL) {
      $this->transactionId = $transaction_id;
      $this->load();
    }
    elseif ($type !== NULL && $uid !== NULL) {
      $this->uid = $uid;
      $this->type = $type;
      $this->created = REQUEST_TIME;
    }
    else {
      throw new Exception(t('Cannot initialize POS transaction: invalid arguments supplied.'));
    }

    if ($cashier !== NULL) {
      $this->cashier = $cashier;
    }

    $this->collectBases();
  }

  /**
   * __call() magic method.
   */
  public function __call($name, $arguments) {
    // Attempt to invoke any Base actions if an unknown method is invoked on the
    // Transaction class.
    return $this->invokeAction($name, $arguments);
  }

  /**
   * // @TODO: this needs to be documented better.
   *
   * @param string $action_name
   *   The name of the action to invoke.
   * @param ...
   *   any additional arguments will be passed to the method.
   *
   * @return mixed
   *   Whatever the result of the invoked method is.
   * @throws \Exception
   */
  public function doAction($action_name) {
    $this->updateCashier();

    $args = array_slice(func_get_args(), 1);
    return $this->invokeAction($action_name, $args);
  }

  /**
   * Allows Base Classes to notify other Base Classes when specific events
   * occur.
   *
   * This will invoke the defined methods of any base classes that have
   * subscribed to specific events.
   *
   * @param string $event_name
   *   The name of the event. This will invoke the methods of any base classes
   *   subscribed to this event.
   * @param ...
   *   Any additional arguments will be passed to the event methods.
   *
   * @throws \Exception
   */
  public function invokeEvent($event_name) {
    if (isset($this->events[$event_name]['subscriptions'])) {
      $args = array_slice(func_get_args(), 1);

      foreach ($this->events[$event_name]['subscriptions'] as $base_class => $methods) {
        foreach ($methods as $method) {
          call_user_func_array(array(
            $this->bases[$base_class],
            $method,
          ), $args);
        }
      }
    }
    else {
      throw new Exception(t('Cannot invoke event, the @event event is not defined.', array(
        '@event' => $event_name,
      )));
    }
  }

  /**
   * Retrieves the commerce order associated with this transaction.
   */
  public function getOrder() {
    if ($this->orderId) {
      return $this->order ? $this->order : $this->loadOrder();
    }
    else {
      return FALSE;
    }
  }

  /**
   * Sets the transaction's order.
   */
  public function setOrder($order) {
    $this->order = $order;
    $this->orderId = $order->order_id;
  }

  /**
   * Retrieves the entity metadata wrapper for this transaction's order.
   *
   * The order wrapper is made available as a property on the object because
   * it's used so often by subclasses and other functionality, so there's no
   * point in creating a new wrapper all of the time.
   *
   * @return EntityDrupalWrapper|bool
   */
  public function getOrderWrapper() {
    if ($this->orderId) {
      if (!$this->order) {
        $this->loadOrder();
      }

      $this->checkOrderWrapper();

      return $this->orderWrapper;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Loads the associated commerce order from the database.
   */
  public function loadOrder() {
    if ($this->orderId) {
      $this->order = commerce_order_load($this->orderId);
      return $this->order;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Switches the transaction order's status back from parked to being created.
   */
  public function unpark() {
    $this->updateCashier();

    $this->getOrder();
    if (!empty($this->order)) {
      $this->order->status = 'commerce_pos_in_progress';
      commerce_order_save($this->order);
    }
  }

  /**
   * Voids a transaction.
   */
  public function void() {
    $this->updateCashier();

    $this->getOrder();
    if (!empty($this->order)) {
      $this->order->status = 'commerce_pos_voided';
      commerce_order_save($this->order);
    }
  }

  /**
   *
   * @param $action_name
   * @param $arguments
   */
  protected function invokeAction($action_name, $arguments) {
    if (isset($this->actions[$action_name]['class'])) {
      $base_class = $this->bases[$this->actions[$action_name]['class']];

      $this->invokeEvent($action_name . 'Before', $arguments);

      $result = call_user_func_array(array(
        $base_class,
        $action_name,
      ), $arguments);

      // Add the result of the initial method call to our arguments so that it's
      // always the last argument passed to any 'after' subscriptions.
      array_push($arguments, $result);

      $this->invokeEvent($action_name . 'After', $arguments);

      return $result;
    }
    else {
      throw new Exception(t('The transaction base method @name does not exist.', array(
        '@name' => $action_name,
      )));
    }
  }

  /**
   * Returns or creates a new order wrapper as necessary.
   *
   * Metadata wrappers lose their reference to the original object when they're
   * loaded from form_state variables. As a result, we need to check and make
   * sure that the order wrapper is still indeed referencing the order.
   */
  protected function checkOrderWrapper() {
    if ($this->order) {
      if (($this->orderWrapper && $this->orderWrapper !== $this->order) ||
        (!$this->orderWrapper)
      ) {
        $this->orderWrapper = entity_metadata_wrapper('commerce_order', $this->order);
      }
    }
  }

  /**
   * Loads the transaction from the database.
   */
  protected function load() {
    if ($this->transactionId) {
      $result = db_select(self::TABLE_NAME, 't')
        ->fields('t')
        ->condition('transaction_id', $this->transactionId)
        ->execute()
        ->fetchAssoc();

      if ($result) {
        $this->uid = $result['uid'];
        $this->orderId = $result['order_id'];
        $this->type = $result['type'];
        $this->registerId = $result['register_id'];
        $this->created = $result['created'];
        $this->changed = $result['changed'];
        $this->completed = $result['completed'];
        $this->cashier = $result['cashier'];

        if (empty($result['data'])) {
          $this->data = array();
        }
        else {
          $this->data = unserialize($result['data']);
        }

        return $this;
      }
      else {
        $this->transactionId = 0;
        return FALSE;
      }
    }
    else {
      throw new Exception(t('Cannot load POS transaction: it does not have a transaction ID!'));
    }
  }

  /**
   * Determines if the cashier property should be updated and saves the
   * transaction if an update occurred.
   */
  protected function updateCashier() {
    $current_cashier = commerce_pos_cashier_get_current_cashier();
    if ($this->cashier !== $current_cashier) {
      $this->cashier = $current_cashier;
      $this->doAction('save');
    }
  }

  /**
   * Checks for any modules defining additional base classes to be added to this
   * transaction and registers their action and subscriptions.
   *
   * Actions are invoked by calling the transaction's doAction method.
   *
   * Subscription methods are automatically called before and after an action
   * is invoked.
   */
  private function collectBases() {
    foreach (module_invoke_all('commerce_pos_transaction_base_info') as $base_info) {
      // Only add the base class if it belongs to this type, or if it didn't
      // specify any types that it belongs to.
      if (!isset($base_info['types']) || in_array($this->type, $base_info['types'])) {
        $class_name = $base_info['class'];
        $this->bases[$class_name] = new $class_name($this);
        $base_class = &$this->bases[$class_name];

        // Register all actions provided by the Base class.
        foreach ($base_class->actions() as $action_method) {
          if (!isset($this->actions[$action_method])) {
            $this->actions[$action_method] = array(
              'class' => $class_name,
              'before' => array(),
              'after' => array(),
            );

            $event_definition = array(
              'subscriptions' => array(),
            );

            $this->events[$action_method . 'Before'] = $event_definition;
            $this->events[$action_method . 'After'] = $event_definition;
          }
          else {
            throw new Exception(t('Cannot add action @action, it has already been defined!', array(
              '@action' => $action_method,
            )));
          }
        }

        // Register all events provided by the Base class.
        foreach ($base_class->events() as $event_name) {
          if (!in_array($event_name, $this->events)) {
            $this->events[$event_name] = array(
              'subscriptions' => array(),
            );
          }
          else {
            throw new Exception(t('Cannot load base class <strong>@name</strong>, the event <strong>@event</strong> has already been defined.', array(
              '@name' => $class_name,
              '@event' => $event_name,
            )));
          }
        }
      }
    }

    // Clear references to any prior objects.
    unset($base_class);

    // Now that the Base Classes have been added, store their event
    // subscriptions.
    /* @var CommercePosTransactionBaseInterface $base_class */
    foreach ($this->bases as $class_name => $base_class) {
      foreach ($base_class->subscriptions() as $event_name => $event_methods) {
        if (isset($this->events[$event_name])) {
          $this->events[$event_name]['subscriptions'][$class_name] = $event_methods;
        }
        else {
          throw new Exception(t('Cannot subscribe base class @name to event @event, that event does not exist.', array(
            '@name' => $class_name,
            '@event' => $event_name,
          )));
        }
      }
    }
  }
}
