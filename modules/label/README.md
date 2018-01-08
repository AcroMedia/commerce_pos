# Commerce POS Label

Provides the ability to print labels for products.

## Using the module

Enable it and you get a new option added to your main POS page
that allows you to pick products and print labels for them,
complete with barcodes if UPC codes are set

## Supporting new label types.

Add a new format to a module_name.label_formats.yml file,
example below or you can check out the same file in this module

```yaml
commerce_pos_label_30334:
  title: Dymo 30334 - 1 1/4" x 2 1/4"
  css: 'css/labels/commerce_pos_label.label.dymo.css'
  dimensions:
    width: 2.25
    height: 1.0
```

The module does not currently support per label type twig files,
but it will in the future

### SASS/CSS

The naming convention is css/labels/module_name.label.labelname.css 
Ultimately that is just for ease of use and you can name them
whatever you want if you feel like it.
