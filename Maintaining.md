### Releasing / Publishing Module to Drupal.Org
Module Project URL: https://www.drupal.org/project/midtrans_commerce
Drupal Git Repository URL: https://git.drupalcode.org/project/midtrans_commerce

#### HOW TO:
##### Prepare:
1. Get drupal git access with ssh key
2. Clone or add remote url : `git remote add drupal git@git.drupal.org:project/midtrans_commerce.git`
3. Update module (new features, bug fixes, etc)

##### Update Version:
1. Open midtrans_commerce.info.yml and remove (version, project, datestamp), this will automatically added by drupal when user install the module, sample:
    ```
    # Information added by Drupal.org packaging script on 2021-09-07
    version: '2.1.0-alpha2'
    project: 'midtrans_commerce'
    datestamp: 1631003789
    ```
2. update version in Readme.md file

##### Release:
1. Open https://www.drupal.org/project/midtrans_commerce
2. Scroll to bottom and click Add new release
3. Please follow this docs to release to drupal.org
Release naming conventions: https://www.drupal.org/node/1015226
Creating project release: https://www.drupal.org/docs/develop/git/git-for-drupal-project-maintainers/creating-a-project-release
Valid release branch examples:
2.0.x
2.1.x
Valid release tag examples:
2.0.1
2.1.0-alpha1
2.1.0-alpha2
2.1.0
