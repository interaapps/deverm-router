

# deverm-router 2.1
Deverm-php-Router is an open-source PHP page-router.

[Standard code](#StandardCode)
[News](#Updates)
[Todo](#Todo)

## StandardCode

### Index.php
```php
<?php

<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);// */

require "../app/route.php";
require "../devermrouter/route.php";

$router = new Router($views_dir, $templates_dir);
$router->set($route);
$router->route();
```


### .htaccess
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.+)$ index.php [QSA,L]
```

### Routing

```php

<?php
/*

"/"          =   Homepage
"@__404__@"  =   Page not found

(Do not use duplicated keys!)

*/

$views_dir      =  "../views/";
$templates_dir  =  "../views/templates/";

$route = [
  "/"                        =>     "homepage.php",
  "/about"                   =>     "about.php",
  "/custom/[getit][url]"     =>     "customtest.php",
  "@__404__@"                =>     "404.php"
];

```

## Updates

### 2.1

```
Fixed bugs
```

### 2.0

```
Made everything nicer.
Autoload function, new Routevar system with regex, better function support for routes and more.
```
 
### 1.3

```
Fixed bugs. Added Method Routing without without classes.
Changed route construct. (You have to set the views-dir first [new router($views_dir, $templates_dir)])
```

### 1.2

```
Fixed bugs. Added Method Routing.
Added Request Methods
```

## Todo
Adding soon
 - [ ]  -
 - [x] -


