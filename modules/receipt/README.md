The receipt submodule provides for receipt printing. You may configure custom
receipt header/footer at admin/commerce/config/pos/receipt.

This module depends on the jQuery print plugin which you should reside
in your /libraries directory at the docroot.

We can install jQuery.print using composer or by downloading manually.

### Composer method
1. Open composer.json file of your site.
2. Add `"DoersGuild/jQuery.print": "master"` to the `"require"`. Ex-
```
"require": {
    .
    .
    "DoersGuild/jQuery.print": "master"
 }
```
3. Add `"libraries/{$name}": ["type:drupal-library"]` 
to the `"installer-paths"`. Ex-
```
"installer-paths": {
    .
    .
    "libraries/{$name}": ["type:drupal-library"]
}
```
4. Add the below lines of code to `"repositories"` -

```
{
    "type": "package",
    "package": {
        "name": "DoersGuild/jQuery.print",
        "version": "master",
        "type": "drupal-library",
        "source": {
            "url": "https://github.com/DoersGuild/jQuery.print.git",
            "type": "git",
            "reference": "origin/master"
        }
    }
}
```
5. Run `composer update DoersGuild/jQuery.print`.

### Manual method
1. Create `libraries` folder if it doesn't existin the docroot of website.
2. `cd` into libraries folder and run 
`git clone https://github.com/DoersGuild/jQuery.print.git`.
