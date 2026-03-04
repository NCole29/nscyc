# Shortcut Per Role

The Shortcut Per Role module is a simple utility module which allows a
pre-defined Drupal shortcut set to be assigned to each role on the website.

Users logging into the site who have permission to use the Toolbar and the
shortcuts module will see the shortcut set for the role they have.

When a use has more than one role with different shortcut sets, the shortcut
set assigned to the role with the highest weight is shown.


## Requirements

This module requires the [Shortcut][] module from Drupal core to be enabled.

[Shortcut]: https://www.drupal.org/docs/8/core/modules/shortcut


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see [Installing Drupal Modules][].

[Installing Drupal Modules]: https://www.drupal.org/docs/extending-drupal/installing-drupal-modules


## Configuration

1.  Ensure you have one or more roles created; or use the standard Drupal roles
    of 'Administrator' and 'Authenticated user'.

1.  Created one or more shortcut sets following the [documentation][].

    [documentation]: https://www.drupal.org/docs/7/administering-drupal-7-site/working-with-the-shortcut-bar

1.  Go to this module's configuration page via the Drupal Administration Menu:

    Admin » Configuration » User interface » Shortcuts » Shortcuts Per Role

        /admin/config/user-interface/shortcut/roles

1.  Assign the appropriate shortcut set for each role.


## Maintainers

* James Wilson - [jwilson3](https://www.drupal.org/u/jwilson3)
* Ankit Babbar - [webankit](https://www.drupal.org/user/533078)
* Max Kuzmych - [mkdok](https://www.drupal.org/u/mkdok)
