# deverm-router 5.2
Deverm-php-Router is an open-source PHP-router.

```bash
uppm i deverm
# Or composer
composer require interaapps/deverm
```

### public/index.php
```php
use de\interaapps\ulole\router\Router;
use de\interaapps\ulole\router\Request;
use de\interaapps\ulole\router\Response;

// Set root directory of the project
chdir('..'); 
$router = new Router();

// Using method or function
$router->get("/", TestController::test(...));
$router->get("/test", test(...));

// Using anonymous function
$router->get("/test/{test}", function(Request $req, Response $res, string $test){
    $res->json([
        "given_test" => $test // or $req->getRouteVar(0) 
    ]);
});

// Including php files
$router->setIncludeDirectory("resources/views");
$router->get("/", "homepage.php");

$router->notFound(function(Request $req, Response $res){
    echo "Not found :.(";
});

// Before interceptor
$router->before("/dashboard/{?*:path}", function(Request $req, Response $res) : bool {
    if ($loggedIn) {
        $req->attrib("loggedIn", true);
    } else {
        return true; // Intercepts. The notFound page will be called!
    }
    return false;
});

$router->get("/dashboard/bills", function(Request $req, Response $res){
    if ($req->attrib("loggedIn")) {
        echo "Logged in!";
    }
});


$router->notFound(function(Request $req, Response $res){
    return "page not found :(";
});

// If using method. (The three dots are a special syntax from php 8.1)
$router->notFound(NotFoundHandler::handle(...));

// Running the app
$router->run();
```

## Using controllers
```php
<?php
use de\interaapps\ulole\router\attributes\Controller;
use de\interaapps\ulole\router\attributes\Route;
use de\interaapps\ulole\router\attributes\methods\Get;
use de\interaapps\ulole\router\attributes\methods\Post;
use de\interaapps\ulole\router\Request;
use de\interaapps\ulole\router\Response;

#[Controller("/users")]
class UserController {
    #[Get("/{i+:id}")]
    public function getUser(Request $req, Response $res, int $id) {
        return User::table()->where("id", $id)->first();
    }
    
    #[Post]
    public function getUser(Request $req, Response $res, #[Body] NewUserRequest $newUserRequest) {
        $user = (new User())
            ->setName($newUserRequest->name)
            ->setPassword($newUserRequest->password)
            ->save();
            
        return $user->id;
    }
    
    #[Route("/test", method: "GET|POST")]
    public function multipleMethods() {}
}

// NewUserRequest.php
class NewUserRequest {
    public string name;
    public string password;
}
```


### public/.htaccess
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.+)$ index.php [QSA,L]
```

### Route variables
```php
// Simple
$router->get("/{example}", function (string $example) {});

// Optional route variable
$router->get("/{?string:example}", function (string $example) {});

// Integer
$router->get("/{i:example}", function (int $example) {});

// Positive Integer
$router->get("/{i+:example}", function (int $example) {});

// Negative Integer
$router->get("/{i-:example}", function (int $example) {});

// Float (You can also use negative or positive only with -/+)
$router->get("/{f:example}", function (float $example) {});
```

### Parameters and Parameter Attributes
```php
use de\interaapps\ulole\router\attributes\Attrib;
use de\interaapps\ulole\router\attributes\Body;
use de\interaapps\ulole\router\attributes\Controller;
use de\interaapps\ulole\router\attributes\QueryParam;
use de\interaapps\ulole\router\attributes\Route;
use de\interaapps\ulole\router\attributes\RouteVar;
use de\interaapps\ulole\router\Request;
use de\interaapps\ulole\router\Response;

// Some parameters will be autoamtically filled by the deverm-router
$router->get("/test/{test}", function (
    Request $req, // Request and Response will automatically be injected if type is given
    Response $res,
    string $test, // Will be automatically filled to the route variable, because it's the same name
    #[QueryParam] string $test, // Query Parameter like /test/hello?test=example
    #[Attrib] User $user // Get the value, set before by $req->attrib("user", ...)
) {
});

class TestRequest {
    public string $hey;
}
// If json body is being sent, it'll be parsed into a object of the type class
$router->post("/test", function (#[Body] TestRequest $request) {
    echo $request->hey;
})
```