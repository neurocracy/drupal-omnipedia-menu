This contains the source files for the "*Omnipedia - Menu*" Drupal module, which
provides menu items and menu-related functionality for
[Omnipedia](https://omnipedia.app/).

⚠️ ***[Why open source? / Spoiler warning](https://omnipedia.app/open-source)***

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

* [Drupal 9.5 or 10](https://www.drupal.org/download) ([Drupal 8 is end-of-life](https://www.drupal.org/psa-2021-11-30))

* PHP 8.1

* [Composer](https://getcomposer.org/)

## Drupal dependencies

Before attempting to install this, you must add the Composer repositories as
described in the installation instructions for these dependencies:

* The [`omnipedia_core`](https://github.com/neurocracy/drupal-omnipedia-core), [`omnipedia_date`](https://github.com/neurocracy/drupal-omnipedia-date), and [`omnipedia_main_page`](https://github.com/neurocracy/drupal-omnipedia-main-page) modules.

----

# Installation

## Composer

### Set up

Ensure that you have your Drupal installation set up with the correct Composer
installer types such as those provided by [the `drupal/recommended-project`
template](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates#s-drupalrecommended-project).
If you're starting from scratch, simply requiring that template and following
[the Drupal.org Composer
documentation](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
should get you up and running.

### Repository

In your root `composer.json`, add the following to the `"repositories"` section:

```json
"drupal/omnipedia_menu": {
  "type": "vcs",
  "url": "https://github.com/neurocracy/drupal-omnipedia-menu.git"
}
```

### Patching

This provides [one or more patches](#patches). These can be applied automatically by the the
[`cweagans/composer-patches`](https://github.com/cweagans/composer-patches/tree/1.x)
Composer plug-in, but some set up is required before installing this module.
Notably, you'll need to [enable patching from
dependencies](https://github.com/cweagans/composer-patches/tree/1.x#allowing-patches-to-be-applied-from-dependencies) (such as this module 🤓). At
a minimum, you should have these values in your root `composer.json` (merge with
existing keys as needed):


```json
{
  "require": {
    "cweagans/composer-patches": "^1.7.0"
  },
  "config": {
    "allow-plugins": {
      "cweagans/composer-patches": true
    }
  },
  "extra": {
    "enable-patching": true,
    "patchLevel": {
      "drupal/core": "-p2"
    }
  }
}

```

**Important**: The 1.x version of the plug-in is currently required because it
allows for applying patches from a dependency; this is not implemented nor
planned for the 2.x branch of the plug-in.

### Installing

Once you've completed all of the above, run `composer require
"drupal/omnipedia_menu:^4.0@dev"` in the root of your project to have
Composer install this and its required dependencies for you.

## Patches

This module provides [a Drupal core
patch](https://www.drupal.org/project/drupal/issues/3165305#comment-14058586);
this can be automatically applied if you have [`cweagans/composer-patches`
installed and
configured to allow patching from dependencies](https://github.com/cweagans/composer-patches#allowing-patches-to-be-applied-from-dependencies).


----

# Patches

The following patches are supplied (see [Patching](#patching) above):

* Drupal core:

  * [UrlGenerator improperly escapes a colon in the path [#3165305]](https://www.drupal.org/project/drupal/issues/3165305#comment-14058586)

----

# Major breaking changes

The following major version bumps indicate breaking changes:

* 4.x:

  * Requires Drupal 9.5 or [Drupal 10](https://www.drupal.org/project/drupal/releases/10.0.0) with compatibility and deprecation fixes for the latter.

  * Increases minimum version of [Hook Event Dispatcher](https://www.drupal.org/project/hook_event_dispatcher) to 3.1, removes deprecated code, and adds support for 4.0 which supports Drupal 10.
