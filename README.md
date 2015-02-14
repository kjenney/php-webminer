php-webminer -- Extract data using Selenium, QueryPath and PHP
==============================================================

##  DESCRIPTION

This project aims to create a standard way of extracting data from a web page using Selenium WebDriver, QueryPath, and an XML file which specifies which components to extract and how to output the results.

### Input XML
The input XML defines all of the aspects of the web site and the data you wish to extract. Samples are included in the /examples folder.


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

*   Then when you create a session, be sure to pass the url to where your server is running.

        // This would be the url of the host running the server-standalone.jar
        $host = 'http://localhost:4444/wd/hub'; // this is the default
