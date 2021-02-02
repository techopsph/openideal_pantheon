TRANSACTION DRUPAL MODULE
=========================

CONTENTS OF THIS FILE
---------------------

 * Summary
 * Requirements
 * Installation
 * Configuration
 * Usage
 * Contact


SUMMARY
-------

This module provides generic support for transactional operations linked to
content entities. An example of transactional operation is an accounting
account, where the content entity acts as the account and each transaction is a
movement.

Different transactional flows can be created in addition to accounting, such as
tracking/logging systems, workflows, or your own by integration with rules or
programming a transactor.

The parts involved are:

 * target entity
   the content entity to which the transaction flow is linked to

 * transaction type
   that defines the transactional flow

 * transactor
   a plugin that performs the transaction logic

 * transactions
   content entities where the data of each transaction is stored


REQUIREMENTS
------------

The module depends on the "dynamic entity reference" module to refer the target
entity in transactions. This dependency will be removed once the support is in
core, see https://www.drupal.org/project/drupal/issues/2407587 for more
information.


INSTALLATION
------------

Install as usual, see https://www.drupal.org/node/1897420 for further
information. You might rebuild the cache after (un)installation.


CONFIGURATION
-------------

Module configuration is available at Manage -> Configuration -> Workflow ->
Transaction Types (/admin/config/workflow/transaction).


USAGE
-----

The basic steps are:

 * create a transaction type, choose the target entity type and the desired
   transactor. By enable the local task link option, 'tab' will appear in
   applicable entities labeled with the transaction type name

 * go to any target entity to work with. Create your first transaction in the
   transaction tab

 * execute the transaction to perform the operation


CONTACT
-------

Current maintainers:
* Manuel Adan (manuel.adan) - https://www.drupal.org/user/516420
