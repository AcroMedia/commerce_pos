<?php

/**
 * @file
 * commerce_pos_receipt.rules_defaults.inc
 */

/**
 * Implements hook_default_rules_configuration().
 */
function commerce_pos_receipt_default_rules_configuration() {
  $configs = array();
  $rule = '{ "rules_commerce_pos_send_email_receipt" : {
      "LABEL" : "Commerce POS: Send email receipt",
      "PLUGIN" : "reaction rule",
      "OWNER" : "rules",
      "REQUIRES" : [ "commerce_pos_receipt", "rules", "message_notify", "commerce_pos" ],
      "ON" : { "commerce_pos_transaction_completed" : [] },
      "IF" : [
        { "commerce_pos_receipt_rules_condition_check_email_receipt" : { "commerce_order" : [ "order" ] } }
      ],
      "DO" : [
        { "entity_create" : {
          "USING" : {
            "type" : "message",
              "param_type" : "commerce_pos_email_receipt",
              "param_user" : [ "order:owner" ]
            },
            "PROVIDE" : { "entity_created" : { "entity_created" : "Created entity" } }
          }
        },
        { "data_set" : {
          "data" : [ "entity-created:commerce-pos-order-id" ],
            "value" : [ "order:order-id" ]
          }
        },
        { "message_notify_process" : {
          "message" : [ "entity-created" ],
            "save_on_fail" : "0",
            "save_on_success" : "0"
          }
        }
      ]
    }
  }';

  $configs['rules_commerce_pos_send_email_receipt'] = rules_import($rule);
  return $configs;
}
