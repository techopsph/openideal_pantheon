# Administer Users by Role

## Contents of this file

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers

## Introduction

Administer Users by Role allows site builders to set up fine-grained
permissions for allowing "sub-admin" users to manage other users based on the
target user's role.

The module defines new permissions to control access to edit/delete users and
assign roles - more specific than Drupal Core's all-or-nothing 'Administer
users' and 'Administer permissions'. It also provides a 'Create new users'
permission and fine-grained permissions for viewing users.

* For a full description of the module, visit the project page:
[https://www.drupal.org/project/administerusersbyrole](https://www.drupal.org/project/administerusersbyrole)

* To submit bug reports and feature suggestions, or to track changes:
[https://www.drupal.org/project/issues/administerusersbyrole](https://www.drupal.org/project/issues/administerusersbyrole)

## Requirements

This module requires no modules outside of Drupal core.

## Installation

Install the Administer Users by Role module as you would normally install a
contributed Drupal module. Visit
[https://www.drupal.org/node/1897420](https://www.drupal.org/node/1897420) for
further information.

## Configuration

Use the configuration settings (Administration > People > Administer Users) to
classify each role.

* Safe - Grants sub-admins the ability to manage users with that role if they
  have the related permission such as 'Edit users with safe roles'.
* Unsafe - Means sub-admins cannot manage users with that role. For example,
  the "admin" role is always unsafe.
* Custom - Allows for more selective access determined by extra permissions for
  that role.

The sub-admin can access a target user provided they have access to all of
that user's roles.

## Maintainers

* Adam Shepherd (AdamPS) -
[https://www.drupal.org/u/adamps](https://www.drupal.org/u/adamps)
* Steve Mokris (smokris) -
[https://www.drupal.org/u/smokris](https://www.drupal.org/u/smokris)
* Tom Kirkpatrick (mrfelton) -
[https://www.drupal.org/u/mrfelton](https://www.drupal.org/u/mrfelton)

Supporting organizations:

* AlbanyWeb -
[https://www.drupal.org/albanyweb](https://www.drupal.org/albanyweb)
