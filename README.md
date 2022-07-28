# deverm-router 5.3
Deverm-php-Router is an open-source PHP-router.

## Contents
- [Installation](#installation)
- [Getting Started](#publicindexphp)
- [Controller](#using-controllers)
- [Route Variables](#route-variables)
- [Parameter Injection](#parameter-injection)
- [Middlewares](#middlewares)
- [Response Transformers](#response-transformers)


### Installation
Deverm-Router requires `>PHP8.1`
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

// If an object or array is returned in a handler, it'll be transformed into JSON
$router->jsonResponseTransformer();


$router->get("/test/{test}", function(Request $req, Response $res, string $test){
    return [
        "given_test" => $test
    ];
});

// Using method or function
$router->get("/test", test(...));

// Including php files
$router->setIncludeDirectory("resources/views");
$router->get("/", "homepage.php");

$router->notFound(function(Request $req, Response $res){
    echo "Not found :.(";
});

// Everytime an Exception gets thrown in request handlers
$router->exceptionHandler(function (Exception $e, Request $req) {
    return [
        "message" => $e->getMessage(),
        "error" => true
    ];
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

// Running the app
$router->run();
```

## Using controllers
```php
<?php
use de\interaapps\ulole\router\attributes\Controller;
use de\interaapps\ulole\router\attributes\Route;
use de\interaapps\ulole\router\attributes\With;
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
$router->get("/{name}", function (string $name) {
    return "Hello {$name}";
});

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

### Parameter Injection
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

### Middlewares
```php
$router->middleware("admin", function (Request $req, Response $res, #[Attrib] User $user) {
    if (!$user->isAdmin())
        throw new Exception("Not logged in!"); 
});

$router->get("/admin/test", function () {
    // ...
}, ["admin"]);

#[Controller("/admin")]
#[With("admin")] // For all routes in this controller
class MyController {
    #[Get]
    #[With("admin")] // For a specific route
    public function index(){}
}

$router->addController(new MyController);
```

### Response Transformers
```php
// Custom response transformer
$router->responseTransformer(function (Request $req, Response $res, mixed $body) {
    if ($body instanceof TestResponse) {
        return "This is my field ".$body->myField;
    }
    
    // Next response transformer
    return null;
});

// JSON Transformer
// Transforms objects, arrays and booleans into JSON. If first parameter true, strings as well.
$router->jsonResponseTransformer();

$router->get("/", function(){
    return ["Hello"];
});
// ["Hello"]

$router->get("/test", function(){
    return new TestResponse(myField: "Yay");
});
// This is my field yay
```