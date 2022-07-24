<?php
namespace de\interaapps\ulole\router;

use Closure;
use de\interaapps\jsonplus\JSONPlus;
use de\interaapps\ulole\router\attributes\Controller;
use de\interaapps\ulole\router\attributes\Route;
use ReflectionClass;

class Router {
    private array $routes;
    private string $includeDirectory;
    private $notFound;
    private array $beforeInterceptor;
    private Closure $matchProcessor;

    private bool $instantMatches = false;
    private bool $hasInstantMatch = false;

    private JSONPlus $jsonPlus;
    
    public function __construct() {
        $this->routes = [];
        $this->includeDirectory = './';
        $this->beforeInterceptor = [];
        $this->matchProcessor = function($matches){
            $body = "";
            if (defined('STDIN'))
                $body = stream_get_contents(STDIN);
            $request = new Request($this, $body, $matches["routeVars"]);
            $response = new Response;
            
            return [$request, $response]; // Return params or false (intercepts)
        };
        $this->jsonPlus = JSONPlus::createDefault();
    }

    public function run(bool $showNotFound = true): bool {
        if ($this->instantMatches && $this->hasInstantMatch) return true;

        $requestMethod = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $path=>$route) {
            $matches = $this->matches($path);
            if ($matches !== false && isset($route[$requestMethod])) {
                $currentRoute = $route[$requestMethod];
                
                $params = ($this->matchProcessor)($matches);
                $intercepted = false;
                foreach ($this->beforeInterceptor as $interceptorPath => $beforeInterceptorCallable) {
                    $interceptorMatches = $this->matches($interceptorPath);
                    if ($interceptorMatches !== false) {
                        $interceptorResult = $beforeInterceptorCallable(...$params);
                        if ($interceptorResult !== null)
                            $intercepted = $interceptorResult;
                    }
                }
                if ($params !== false && !$intercepted) {
                    $out = $this->invoke($currentRoute, array_merge($params, $matches['routeVars']));
                    if (is_string($out))
                        echo $out;
                    else if ($out == null) {
                    } else if (is_array($out) || is_object($out)) {
                        header('Content-Type: application/json');
                        echo $this->jsonPlus->toJson($out);
                    }
                    return true;
                }
            }
        }
        if (!$showNotFound)
            return false;
        // Page not found
        header('HTTP/1.1 404 Not Found');
        if ($this->notFound !== null) {
            $invoked = $this->invoke($this->notFound, ($this->matchProcessor)(["routeVars"=>[]]));
            if (is_array($invoked) || is_object($invoked)) {
                header('Content-Type: application/json');
                echo $this->jsonPlus->toJson($invoked);
            } else if ($invoked !== null) {
                echo $invoked;
            }
        } else 
            echo "Page not found";
        return false;
    }

    public function matches($url) : false|array {
        $request = strtok($_SERVER["REQUEST_URI"], '?');
        $matches = [];

        if(preg_match_all('#^' . $url . '$#', $request, $matches)) {
            $routeVars = [];
            foreach ($matches as $key=>$val) {
                if (isset($val[0]) && $val[0] != $request)
                    $routeVars[$key] = $val[0];
            }
            return [
                'routeVars' => $routeVars,
                'url'       => $url,
                'matches'   => $matches
            ];
        }

        return false;
    }

    public function invoke(callable|string $callable, array $params = []){
        if (is_callable($callable)) {
            return call_user_func_array($callable, $params);
        } else if (is_string($callable)) {
            return (include $this->includeDirectory.'/'.$callable);
        }
    }

    public function addRoute($route, $methods, $callable){
        if ($this->instantMatches && $this->hasInstantMatch) return $this;
        if ($this->instantMatches){
            $match = $this->matches($route);
            if ($match !== false) {
                if ($this->run(false)) {
                    $this->hasInstantMatch = true;
                }
            }
        }
        if (!isset($this->routes[$route]))
            $this->routes[$route] = [];
        if (strpos($methods, "|") !== false) {
            foreach (explode("|", $methods) as $method)
                $this->routes[$route][$method] = $callable;
        } else
            $this->routes[$route][$methods] = $callable;

        return $this;
    }

    /**
     * @return $this
     * @throws Null
     */
    public function addController(mixed... $objects): Router {
        foreach ($objects as $object) {
            if ($this->instantMatches && $this->hasInstantMatch) return $this;

            $clazz = get_class($object);

            if (!($clazz instanceof ReflectionClass))
                $clazz = new ReflectionClass($clazz);
            $controller = $clazz->getAttributes(Controller::class);

            foreach ($clazz->getMethods() as $method) {
                foreach ($method->getAttributes() as $attribute) {
                    if ($attribute->getName() == Route::class) {
                        $route = $attribute->newInstance();
                        $path = "";
                        if (isset($controller[0]))
                            $path = $controller[0]->newInstance()->pathPrefix;
                        $path .= $route->path;

                        $this->addRoute($path, $route->method, $method->getClosure($method->isStatic() ? null : $object));
                    }
                }
            }
        }

        return $this;
    }

    public function get($route, $callable): Router{
        return $this->addRoute($route, 'GET', $callable);
    }
    public function post($route, $callable): Router{
        return $this->addRoute($route, 'POST', $callable);
    }
    public function put($route, $callable): Router{
        return $this->addRoute($route, 'PUT', $callable);
    }
    public function patch($route, $callable): Router{
        return $this->addRoute($route, 'PATCH', $callable);
    }
    public function delete($route, $callable): Router{
        return $this->addRoute($route, 'DELETE', $callable);
    }

    public function notFound($notFound): Router{
        $this->notFound = $notFound;
        return $this;
    }

    public function before($route, $callable): Router{
        $this->beforeInterceptor[$route] = $callable;
        return $this;
    }

    public function setIncludeDirectory($includeDirectory): Router{
        $this->includeDirectory = $includeDirectory;
        return $this;
    }

    public function setMatchProcessor($matchProcessor): Router{
        $this->matchProcessor = $matchProcessor;
        return $this;
    }

    public function setInstantMatches(bool $instantMatches): Router
    {
        $this->instantMatches = $instantMatches;
        return $this;
    }

    public function setJsonPlus(JSONPlus $jsonPlus): Router {
        $this->jsonPlus = $jsonPlus;
        return $this;
    }

    public function getJsonPlus(): JSONPlus {
        return $this->jsonPlus;
    }
}
