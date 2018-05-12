Commerce Point of Sale (POS)
============================

## Setup

### UPC
If you wish to use UPC fields, 
you'll want to add the UPC field to your form config. 
It is added to the default product variation type by default 
but not displayed. If you wish to add it to any other product 
variations, just add it as you would any other field.

### Order Types

Order types must have field_cashier and field_register to be compatible with
the POS. The default order type has these automatically, but if you wish to
include any other types you will need to add these fields. Any order types
without these fields will not show in order lookup and will not load in the
POS interface.

### Search

Commerce POS uses Search API for product search,
since it has to do lots of searching and it has to do it fast.

* Set the server and index in the Commerce POS settings.
* Search index should be of product_variations.
* Search index must index the commerce_store field for store filtering.

The config/optional has example setups for server and index,
based on the search_api_db server. If you enable the search_api_db module these
will be installed automatically.

## Contributing

### Naming Conventions

Submodule names should be prefixed with "Commerce POS" to keep things
organized and tidy.
