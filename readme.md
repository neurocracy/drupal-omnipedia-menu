This contains the source files for the "*Omnipedia - Menu*" Drupal module, which
provides menu items and menu-related functionality for
[Omnipedia](https://omnipedia.app/).

⚠️⚠️⚠️ ***Here be potential spoilers. Proceed at your own risk.*** ⚠️⚠️⚠️

----

# Why open source?

We're dismayed by how much knowledge and technology is kept under lock and key
in the videogame industry, with years of work often never seeing the light of
day when projects are cancelled. We've gotten to where we are by building upon
the work of countless others, and we want to keep that going. We hope that some
part of this codebase is useful or will inspire someone out there.

----

# Description

This contains most of Omnipedia's menu and menu item-related code.

Of particular note is the custom menu link entity, its associated plug-in and
deriver, forms, a constraint validator, access control handler, and route
controller; this custom menu link is very similar to the one offered by Drupal
core with one crucial difference: instead of pointing to a discrete route or
path, it points to a wiki node title which allows it to vary by the simulated
wiki date.

Additionally, this module contains the [route controller for the random
page](/src/Controller/RandomPageController.php) menu item.

----

# Requirements

* [Drupal 9](https://www.drupal.org/download) ([Drupal 8 is end-of-life](https://www.drupal.org/psa-2021-11-30))

* PHP 8

* [Composer](https://getcomposer.org/)

----

# Installation

Ensure that you have your Drupal installation set up with the correct Composer
installer types such as those provided by [the ```drupal\recommended-project```
template](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates#s-drupalrecommended-project).
If you're starting from scratch, simply requiring that template and following
[the Drupal.org Composer
documentation](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
should get you up and running.

Then, in your root ```composer.json```, add the following to the
```"repositories"``` section:

```json
"drupal/omnipedia_menu": {
  "type": "vcs",
  "url": "https://github.com/neurocracy/drupal-omnipedia-menu.git"
}
```

Then, in your project's root, run ```composer require
"drupal/omnipedia_menu:3.x-dev@dev"``` to have Composer install the module
and its required dependencies for you.

## Patches

This module provides [a Drupal core
patch](https://www.drupal.org/project/drupal/issues/3165305#comment-14058586);
this can be automatically applied if you have [`cweagans/composer-patches`
installed and
configured to allow patching from dependencies](https://github.com/cweagans/composer-patches#allowing-patches-to-be-applied-from-dependencies).
