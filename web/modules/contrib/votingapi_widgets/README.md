# Voting API Widgets

![Logo](https://www.drupal.org/files/styles/grid-3/public/project-images/Drupal8_Voting_API_Widgets_Logo_DrupalORG.png)

A flexible field based Voting System based on [Votingapi](https://www.drupal.org/project/votingapi).

You need Drupal Version >= 8.2.3. (Multiple form instances on a page)
## Installation

1. Download this module via composer.
2. Download the required [jQuery Bar Rating Plugin](https://github.com/antennaio/jquery-bar-rating) and place it inside of the libraries folder (/libraries).  
   For composer based projects please install the library as described on the [Drupal 8 Documentation](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed).  
   Example:  
   Insert the following repository inside of the Drupal composer.json (/composer.json):
   ```
    {
      "type": "package",
      "package": {
        "name": "antennaio/jquery-bar-rating",
        "version": "1.2.2",
        "type": "drupal-library",
        "dist": {
          "type": "zip",
          "url": "https://github.com/antennaio/jquery-bar-rating/archive/v1.2.2.zip"
        }
      }
    }
   ```
   and execute "composer require antennaio/jquery-bar-rating".
3. Install VotingAPI Widget the [usual way](https://www.drupal.org/documentation/install/modules-themes/modules-8).
### Known issues

Upon installation it is important to save the VotingAPI config at least once, due to a bug in the default shipped config
 of VotingAPI alpha3. That way all global configs are available.
 
# Sponsoring

[Module on drupal.org](https://www.drupal.org/project/votingapi_widgets)

Sponsored by b-connect

![b-connect](https://b-connect.de/sites/all/themes/bctheme/logo.png)

[Hubert Burda Media](https://www.drupal.org/hubert-burda-media)
sponsoring development
