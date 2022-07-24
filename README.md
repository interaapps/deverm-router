

# deverm-router 5.0
Deverm-php-Router is an open-source PHP-router.

### public/index.php
```php
use de\interaapps\ulole\router\Router;
use de\interaapps\ulole\router\Request;
use de\interaapps\ulole\router\Response;

// Set root directory of the project
chdir('..'); 
$router = new Router;

// Using method or function
$router->get("/", TestController::test(...));
$router->get("/test", test(...));

// Using anonymous function
$router->get("/test/(.*)", function(Request $req, Response $res, $test = null){
    $res->json([
        "given_test" => $test // or $req->getRouteVar(0) 
    ]);
});

// Including php files
$router->setIncludeDirectory("resources/views");
$router->get("/", "homepage.php");

$router->notFound(function($req, $res){
    echo "Not found :.(";
});

// Before interceptor
$router->before("/dashboard/(.*)", function($req, $res){
    if ($loggedIn) {
        $req->attrib("loggedIn", true);
    } else {
        return true; // Intercepts. The notFound page will be called!
    }
});

$router->get("/dashboard/bills", function($req, $res){
    if ($req->attrib("loggedIn")) {
        echo "Logged in!";
    }
});


$router->notFound(function($req, $res){
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
use de\interaapps\ulole\router\Request;
use de\interaapps\ulole\router\Response;

#[Controller("/users")]
class UserController {
    #[Route("/[0-9]*", method: 'GET')]
    public function getUser(Request $req, Response $res, int $id) {
        return User::table()->where("id", $id)->first();
    }
    
    #[Route("", method: 'POST')]
    public function getUser(Request $req, Response $res) {
        // Get Post request JSON
        $request = $req->json(NewUserRequest::class);
        $user = (new User())
            ->setName($request->name)
            ->setPassword($request->password)
            ->save();
            
        return $user->id;
    }
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

### Regex examples
`[a-zA-Z0-9_-]+` `a-z`, `A-Z`, `0-9`, `-`, `_`, `/`<br>
`([^/]+)` Every char except `/`<br>
`(.*)` Every char
(More [here](https://www.al-hiwarnews.com/img/hiwar.pdf))
