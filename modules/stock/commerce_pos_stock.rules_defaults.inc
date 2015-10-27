<?php
/**
 * @file
 * commerce_pos_stock.rules_defaults.inc
 */

/**
 * Implements hook_default_rules_configuration().
 */
function commerce_pos_stock_default_rules_configuration() {
  $items = array();
  $items['rules_stock_decrease_when_a_pos_transaction_is_completed'] = entity_import('rules_config', '{ "rules_stock_decrease_when_a_pos_transaction_is_completed" : {
      "LABEL" : "Stock: Decrease when a POS transaction is completed",
      "PLUGIN" : "reaction rule",
      "OWNER" : "rules",
      "TAGS" : [ "stock_control" ],
      "REQUIRES" : [ "rules", "commerce_ss", "commerce_pos" ],
      "ON" : { "commerce_pos_transaction_completed" : [] },
      "IF" : [
        { "data_is" : { "data" : [ "transaction-type" ], "value" : "sale" } },
        { "data_is" : { "data" : [ "order:status" ], "value" : "completed" } }
      ],
      "DO" : [
        { "LOOP" : {
            "USING" : { "list" : [ "order:commerce-line-items" ] },
            "ITEM" : { "list_item" : "Current list item" },
            "DO" : [
              { "commerce_ss_decrease_by_line_item" : { "commerce_line_item" : [ "list-item" ] } }
            ]
          }
        }
      ]
    }
  }');
  $items['rules_stock_increase_when_a_pos_return_is_completed'] = entity_import('rules_config', '{ "rules_stock_increase_when_a_pos_return_is_completed" : {
      "LABEL" : "Stock: Increase when a POS return is completed",
      "PLUGIN" : "reaction rule",
      "OWNER" : "rules",
      "TAGS" : [ "stock_control" ],
      "REQUIRES" : [ "rules", "commerce_ss", "commerce_pos" ],
      "ON" : { "commerce_pos_transaction_completed" : [] },
      "IF" : [
        { "data_is" : { "data" : [ "transaction-type" ], "value" : "return" } },
        { "data_is" : { "data" : [ "order:status" ], "value" : "commerce_pos_returned" } }
      ],
      "DO" : [
        { "LOOP" : {
            "USING" : { "list" : [ "order:commerce-line-items" ] },
            "ITEM" : { "list_item" : "Current list item" },
            "DO" : [
              { "commerce_ss_increase_by_line_item" : { "commerce_line_item" : [ "list-item" ] } }
            ]
          }
        }
      ]
    }
  }');
  return $items;
}
