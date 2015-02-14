php-webminer -- Extract data using Selenium, QueryPath and PHP
==============================================================

##  DESCRIPTION

This project aims to create a standard way of extracting data from a web page using Selenium WebDriver, QueryPath, and an XML file which specifies which components to extract and how to output the results.

### Input XML
The input XML defines all of the aspects of the web site and the data you wish to extract. 

Base XML must be in the following format:

1. Root element must be named "pullcase"
2. Child elemnet "site" must be defined
3. Child element "steps" are recommended as they drive actions 


**Actions**

1. Click
2. Type

**Elements**

1. Input - CSS Selectors used by QueryPath to pull data from a web page
2. Output - Element name of Output XML


Samples are inclusded in the /examples folder.

### Output XML
The definitions in the Input XML define how the output XML will be formatted (element names).


##  GETTING THE CODE

### Github
    git clone git@github.com:kjenney/php-webminer.git

### Packagist
Add the dependency. https://packagist.org/packages/kjenney/php-webminer

    {
      "require": {
        "kjenney/php-webminer": "dev-master"
      }
    }
    
Download the composer.phar

    curl -sS https://getcomposer.org/installer | php

Install the library.

    php composer.phar install
        
   

##  GETTING STARTED

*   All you need as the server for this client is the selenium-server-standalone-#.jar file provided here: http://selenium-release.storage.googleapis.com/index.html

*   Download and run that file, replacing # with the current server version.

        java -jar selenium-server-standalone-#.jar
