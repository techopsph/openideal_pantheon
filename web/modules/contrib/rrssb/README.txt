CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Ridiculously Responsive Social Sharing Buttons module provides the ability
to use the Ridiculously Responsive Social Share Buttons to a Drupal site.

RRSSB are social sharing buttons that you can drop into any website with
attractive SVG-based icons, small download, and browser compatibility. No
3rd-party scripts.

You can choose to add the buttons to the end of certain node types or use the
block to put them wherever you want.

Originally designed as share buttons (where a visitor is encouraged to share
your page on their social stream), the buttons can equally be used as follow
buttons, where the visitor is encouraged to visit your social stream.

 * For a full description of the module visit:
   https://www.drupal.org/project/rrssb

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/rrssb


REQUIREMENTS
------------

This module requires no modules outside of Drupal core, but does require an
external RRSSB library to be downloaded and installed.


INSTALLATION
------------

    1. Download and install the Drupal module as normal.
    2. If you have drush, it should fetch the RRSSB library automatically. If
       not, you can manually run 'drush cache-rebuild' and 'drush rrssbdl'.
    3. Otherwise, manually download the library zip file, using the URL on the
       site "Status report" page to ensure that you get the right version.
    4. Extract the files to sites/all/libraries, and rename rrssb-master to
       rrssb-plus.
    5. Enable the module.

For more information on installing modules, visit:
https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Once the module has been installed and enabled, you can choose your
       buttons and settings on the module configuration page:
       Administration > Configuration > Content > RRSSB > Default.
    2. You can add multiple share/follow blocks with different settings, by
       clicking the 'Add RRSSB Button Set' button on the RRSSB configuration
       page.
    3. After your RRSSB block or blocks are created, you can add them to page
       layout in the block user interface Administration > Structure > Block the
       way you add other blocks.


MAINTAINERS
-----------

 * Adam Shepherd (AdamPS) - https://www.drupal.org/u/adamps
 * Himanshu Dixit (himanshu-dixit) - https://www.drupal.org/u/himanshu-dixit
 * Michael Roberts (mike.roberts) - https://www.drupal.org/u/mike.roberts
