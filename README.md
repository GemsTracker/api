# GemsTracker REST API project
[![Build Status](https://travis-ci.org/GemsTracker/api.svg?branch=master)](https://travis-ci.org/GemsTracker/api)

A new project installation for Gemstracker in Zend Expressive 2.x

It can use Gemstracker library or project specific code.

Most importantly it can be used as a basis to create an API for Gemstracker and use its data models for quick endpoints.

## Installation

To install the project, download a zip or checkout in git and use [composer](https://getcomposer.org/) to get the needed dependencies.

```bash
$ composer install
```

When prompted by Zend packages to inject an entry to the config, use the default option (0): Do not inject.

### Configuration files

Now you will need to add local configuration files. 

Rename config/autoload/database.local.php.dist to database.local.php and fill in the db settings.

If you wish to use Gemstracker Authentication to use the API (e.g. for front-ends in the API) instead of OAuth2, rename config/autoload/gems-auth.local.php.dist to gems-auth.local.php and fill in the linked Gemstracker root path and application environment. If hosted in a subdirectoy change the cookie path.
Make sure your Gemstracker user has the 'pr.api' privilege. 

Finally if you wish to use Development mode, you can either rename development.local.php.dist to development.local.php in both the config and config/autoload directories, or run

```bash
$ composer development-enable
```

### Public/private SSH keys

OAuth2 needs to have a public and private ssh key generated. 
The default location is set in config/autoload/application.global.php and can be changed by renaming config/autoload/application.local.php.dist to application.local.php and changing the 'certificates' element.
If you want to automatically generate these files run 

```bash
$ php bin/generate-keys.php
```

in your project directory.
The keyfile permissions will need to be 600 or 660.