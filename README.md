

# deverm-router 5.0
Deverm-php-Router is an open-source PHP-router.

### public/index.php
```php
use de\interaapps\ulole\router\Router;
use de\interaapps\ulole\router\Request;
use de\interaapps\ulole\router\Response;

chdir('..'); 
$router = new Router;

// Set include start directory
$router->setIncludeDirectory("resources/views");

$router->get("/", "homepage.php");

// Using Controller (Classes).
$router->get("/", "app\\controller\\TestController@test");

// or 
$router->setNamespace("/test", "app\\controller");
$router->get("/test", "TestController@test");

// Ignoring namespace
$router->get("/ignore", '\TestController@test');

// Using closure
$router->get("/test/(.*)", function(Request $req, Response $res, $test = null){
    $res->json([
        "given_test" => $test // or $req->getRouteVar(0) 
    ]);
});

// Running the app
$router->run();
```



### public/.htaccess
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.+)$ index.php [QSA,L]
```

### app/controller/TestController.php
```php
<?php
namespace app\controller;

class TestController {
    public static function test($req, $res){
        return "yep";
    }
}
```

## Updates

### 5.0 (Rebuild update)
```
Rebuild EVERYTHING
```

### 2.2

```
You can now give variables to the view method ( view("view.php", ["variable", "value"]) )
```

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
