# Commerce POS Terminal Example

This module integrates with commerce_pos_terminal to provide a fake terminal
service plugin.

The plugin is for testing or development of the terminal module without
requiring a real payment terminal.

## Testing behaviors

To support testing different scenarios, the plugin has the following behavior:
* Transactions with even dollar amounts always succeed.
* Transactions with odd dollar amounts always fail.
* The process sleeps for one second for every cent in the transaction amount.
  This is to simulate the real delay of a user operating a payment terminal.

## Installation and configuration

Enable the module and select it as the terminal service plugin in the
configuration of the commerce_pos_terminal module.
