USER POINTS DRUPAL MODULE
=========================

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Usage
 * FAQ
 * Maintainers


INTRODUCTION
------------

This module provides point accounts linked to your site users, suitable for:

 * karma system
 * loyalty points
 * discount programs


FEATURES
--------

 * multiple point accounts, per role
 * public / private point system
 * work with standard (custom) fields
 * pending / applied status
 * fieldable transaction per point type


REQUIREMENTS
------------

Required modules:

 * Transaction (https://www.drupal.org/project/transaction)

   The Drupal 8 version of user points is based on the transaction module,
   providing a new transaction type as well as related examples and
   utilities.


INSTALLATION
------------

Install as usual, see https://www.drupal.org/node/1897420 for further
information. You might rebuild the cache after (un)installation.


CONFIGURATION
-------------

A type of user points is a transaction type. Add and manage new user points
accounts at Manage -> Configuration -> Workflow -> Transaction Types
(/admin/config/workflow/transaction).

The userpoints_default sub-module provides some example views and a generic
user points type that is similar to the D7 version. You can use it as a base
configuration or just as an example.


USAGE
-----

The basic steps are:

 * create a type of user points as mentioned in the configuration section
 * go to an user account and create your first transaction in the
   points tab
 * execute the transaction to perform the operation


FAQ
---

### How to assign points to users on certain actions?

The rules module (https://www.drupal.org/project/rules) is recommended to
reward your site users with points on certain actions and under certain
conditions, such as:

 * posting a node (different points can be awarded for different
   node types, e.g. page, story, forum, image, ...etc.)
 * posting a comment
 * login after some inactivity time

A submodule with example rules is provided.

In the other hand, you can do so programmatically by using the Entity API.
For example, to make the current user self-regard 100 points using the
userpoints_default sub-module configuration:

~~~
  // The target user account.
  $target_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
  \Drupal\transaction\Entity\Transaction::create([
    'type' => 'userpoints_default_points',
    'target_entity' => $target_user,
    'field_userpoints_default_amount' => 100,
  ])->execute();
~~~

### How to get the current points balance of an user?

The current points balance of an user is stored in the balance field of the
last executed transaction. For convenience, the transaction module provides
an option to reflect this value in a field of the target entity, that is, a
field in the user account.

The example configuration provided by userpoints_default sub-module creates
a field in the user entity that stores a reflection (copy) of the user
points balance. This field is hidden by default, but can be enabled at the
user entity display setup (/admin/config/people/accounts/display).

It can also be done programmatically, through the transaction service:

~~~
  $target_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
  if ($last_transaction = \Drupal::service('transaction')
    ->getLastExecutedTransaction($target_user, 'userpoints_default_points')) {
    $points = $last_transaction->get('field_userpoints_default_balance')->getString();
  }
~~~


MAINTAINERS
-----------

 * Manuel Adan (manuel.adan) - https://www.drupal.org/u/manueladan
 * Sascha Grossenbacher (Berdir) - https://www.drupal.org/u/berdir
 * Khalid Baheyeldin (kbahey) - https://www.drupal.org/u/kbahey
 * Jacob Redding (jredding) - https://www.drupal.org/u/jredding
 * wafaa - https://www.drupal.org/u/wafaa
