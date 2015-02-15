php-webminer -- Extract data using Selenium, QueryPath and PHP
==============================================================

##  DESCRIPTION

The goal of this project is to create an extensible system for extracting data from web pages. Currently it is using Selenium WebDriver (via php-webdriver), QueryPath, and a configuration file which specifies which components to extract and how to output the results.

### Configuration File
The configuration file defines all of the aspects of the web site and the data you wish to extract. 

It is in XML and must be in the following format:

1. Child elemnet "site" must be defined
2. Child element "steps" are recommended as they drive actions 


**Actions**

1. Click
2. Type

**Elements**

1. Input - CSS Selectors used by QueryPath to pull data from a web page
2. Output - Element name of Output XML


Samples are inclusded in the /examples folder.

### Outputs XML
The definitions in the configuration define how the output will be formatted (element names).


##  INSTALLING

**__GET THE CODE__**

### Github
    git clone git@github.com:kjenney/php-webminer.git

### Packagist
Add the dependency. https://packagist.org/packages/kjenney/php-webminer
    
    {
      "require": {
        "kjenney/php-webminer": "dev-master"
      }
    }

**__BUILD WITH DEPENDENCIES__**   

Download the composer.phar

    curl -sS https://getcomposer.org/installer | php

Install the library.

    php composer.phar install
        
Install PHP5-Tidy

    apt-get install php5-tidy
    yum install php-tidy
 

##  GETTING STARTED

*   All you need as the server for this client is the selenium-server-standalone-#.jar file provided here: http://selenium-release.storage.googleapis.com/index.html

*   Download and run that file, replacing # with the current server version.

        java -jar selenium-server-standalone-#.jar

##  Support
*   Wiki - https://github.com/kjenney/php-webminer/wiki

## Contributing

*   There's still a lot of work that needs to be done, but I welcome any help and/or suggestions.

*   Feel free to create issues and/or recommend features.
