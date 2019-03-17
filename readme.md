
# deverm-router
Deverm-php-Router is an open-source PHP page-router.

## Index.php
```php
<?php
//*    Show errors   (Only for debugging!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);// */

require "../app/route.php";                 // Path of the route file
require "../devermrouter/route.php";		// Path of the devermroute script
route();
```


## .htaccess
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.+)$ index.php [QSA,L]
```

## Routing

```php

<?php


/*
""           =   Homepage
"@__404__@"  =   Page not found

(Do not use duplicated keys!)

*/

$views_dir = "../views/";                 // PATH OF YOUR VIEWS
$templates_dir = "../views/templates/";   // PATH OF YOUR TEMPLATES


$route = [
  ""                       =>   "homepage.php",
  "/about"                 =>   "about.php",
  "/custom/[getit][url]"   =>   "customtest.php",
  "@__404__@"              =>   "404.php"
];
```

