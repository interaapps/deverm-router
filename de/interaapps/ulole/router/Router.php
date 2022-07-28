<?php

namespace de\interaapps\ulole\router;

use Closure;
use de\interaapps\jsonplus\JSONPlus;
use de\interaapps\ulole\router\attributes\Attrib;
use de\interaapps\ulole\router\attributes\Body;
use de\interaapps\ulole\router\attributes\Controller;
use de\interaapps\ulole\router\attributes\methods\Get;
use de\interaapps\ulole\router\attributes\methods\Patch;
use de\interaapps\ulole\router\attributes\methods\Post;
use de\interaapps\ulole\router\attributes\QueryParam;
use de\interaapps\ulole\router\attributes\Route;
use de\interaapps\ulole\router\attributes\RouteVar;
use ReflectionClass;
use ReflectionFunction;

class Router {
    private array $routes;
    private string $includeDirectory;
    private mixed $notFound;
    private array $beforeInterceptor;
    private Closure $matchProcessor;

    private bool $instantMatches = false;
    private bool $hasInstantMatch = false;

    private JSONPlus $jsonPlus;

    public const REGEX_REPLACES = [
        "string" => "[^\/]",
        "f" => "[+-]?([0-9]*[.])?[0-9]",
        "f+" => "[+]?([0-9]*[.])?[0-9]",
        "f-" => "-([0-9]*[.])?[0-9]",
        "i" => "[+\-]?\d",
        "i+" => "[+]?\d",
        "i-" => "\-\d",
        "bool" => "([Tt]rue|[Ff]alse|0|1)",
        "*" => ".",
        "any" => ".",
    ];

    public function __construct() {
        $this->routes = [];
        $this->includeDirectory = './';
        $this->beforeInterceptor = [];
        $this->matchProcessor = function ($matches) {
            $body = "";
            if (defined('STDIN'))
                $body = stream_get_contents(STDIN);
            $request = new Request($this, $body, $matches["routeVars"]);
            $response = new Response($this);

            return [
                "request" => [
                    "type"  => "TYPE",
                    "class" => Request::class,
                    "value" => $request
                ],
                "response" => [
                    "type"  => "TYPE",
                    "class" => Response::class,
                    "value" => $response
                ]
            ];
        };
        $this->jsonPlus = JSONPlus::createDefault();
    }

    public function run(bool $showNotFound = true): bool {
        if ($this->instantMatches && $this->hasInstantMatch) return true;

        $requestMethod = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $path => $route) {
            $matches = $this->matches($path);
            if ($matches !== false && isset($route[$requestMethod])) {
                $currentRoute = $route[$requestMethod];

                $params = ($this->matchProcessor)($matches);
                $intercepted = false;

                foreach ($this->beforeInterceptor as $interceptorPath => $beforeInterceptorCallable) {
                    $interceptorMatches = $this->matches($interceptorPath);
                    if ($interceptorMatches !== false) {
                        $interceptorResult = $this->invoke($beforeInterceptorCallable, $params);
                        if ($interceptorResult !== null)
                            $intercepted = $interceptorResult;
                    }
                }

                if ($params !== false && !$intercepted) {
                    $vars = $params;

                    foreach ($matches['routeVars'] as $name => $value) {
                        $vars[] = [
                            "type"  => "NAME",
                            "name"  => $name,
                            "value" => $value
                        ];
                    }

                    $out = $this->invoke($currentRoute, $vars);

                    if (is_string($out)) {
                        echo $out;
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
            $invoked = $this->invoke($this->notFound, ($this->matchProcessor)(["routeVars" => []]));
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

    public function matches($url): false|array {
        $request = strtok($_SERVER["REQUEST_URI"], '?');
        $matches = [];

        if (preg_match_all($url, $request, $matches)) {
            $routeVars = [];
            foreach ($matches as $key => $val) {
                if (isset($val[0]) && $val[0] != $request)
                    $routeVars[$key] = $val[0];
            }
            return [
                'routeVars' => $routeVars,
                'url' => $url,
                'matches' => $matches
            ];
        }

        return false;
    }

    public function invoke(callable|string $callable, array $params = []) {
        if (is_callable($callable)) {
            $refl = new ReflectionFunction($callable);
            $funcParams = [];
            foreach ($refl->getParameters() as $parameter) {
                foreach ($params as $p) {
                    if ($p["type"] == "TYPE" && $parameter->getType()?->getName() == $p["class"]) {
                        $funcParams[] = $p["value"];
                        continue 2;
                    }

                    if ($p["type"] == "NAME" && $parameter->getName() == $p["name"]) {
                        $funcParams[] = $p["value"];
                        continue 2;
                    }

                    if (count($parameter->getAttributes(Body::class)) > 0) {
                        $type = $parameter->getType()?->getName();
                        $body = "";
                        if (defined('STDIN'))
                            $body = stream_get_contents(STDIN);

                        if ($type === null || $type == 'string')
                            $funcParams[] = $body;
                        else
                            $funcParams[] = $this->jsonPlus->fromJson($body, $type);

                        continue 2;
                    }

                    $attribAttributes = $parameter->getAttributes(Attrib::class);
                    if (count($attribAttributes) > 0) {
                        $funcParams[] = $params["request"]["value"]->attrib($attribAttributes[0]->newInstance()->name ?? $parameter->getName());
                        continue 2;
                    }

                    $queryAttributes = $parameter->getAttributes(QueryParam::class);
                    if (count($queryAttributes) > 0) {
                        $funcParams[] = $params["request"]["value"]->getQuery($queryAttributes[0]->newInstance()->name ?? $parameter->getName());
                        continue 2;
                    }

                    $routeVarAttributes = $parameter->getAttributes(RouteVar::class);
                    if (count($routeVarAttributes) > 0) {
                        $funcParams[] = $params["request"]["value"]->getRouteVar($routeVarAttributes[0]->newInstance()->name ?? $parameter->getName());
                        continue 2;
                    }
                }
                $funcParams[] = null;
            }

            return $refl->invokeArgs($funcParams);
        } else if (is_string($callable)) {
            return (include $this->includeDirectory . '/' . $callable);
        }
    }

    private function createRegex(string $route) : string {
        return "#^" . preg_replace_callback("/\\\{(([A-Za-z0-1_\-+?*\\\]+):)?([A-Za-z0-1_]+)\\\}/", function (array $matches) : string {
            $type = "string";
            if ($matches[2] != "")
                $type = str_replace("\\", "", $matches[2] ?? "string");
            $name = $matches[3];

            if (str_starts_with($type, "?")) {
                $replaced = self::REGEX_REPLACES[substr($type, 1)]."*";
            } else
                $replaced = self::REGEX_REPLACES[$type]."+";

            return "(?<$name>(" . $replaced . "))";
        }, preg_quote($route)) . "$#";
    }

    public function addRoute(string $route, string $methods, callable|string $callable): Router {
        if ($this->instantMatches && $this->hasInstantMatch)
            return $this;

        $route = $this->createRegex($route);

        if ($this->instantMatches) {
            $match = $this->matches($route);
            if ($match !== false) {
                if ($this->run(false)) {
                    $this->hasInstantMatch = true;
                }
            }
        }

        if (!isset($this->routes[$route]))
            $this->routes[$route] = [];

        if (str_contains($methods, "|")) {
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
    public function addController(mixed...$objects): Router {
        foreach ($objects as $object) {
            if ($this->instantMatches && $this->hasInstantMatch) return $this;

            $clazz = get_class($object);

            if (!($clazz instanceof ReflectionClass))
                $clazz = new ReflectionClass($clazz);
            $controllers = $clazz->getAttributes(Controller::class);
            foreach ($controllers as $controller) {
                foreach ($clazz->getMethods() as $method) {
                    foreach ($method->getAttributes() as $attribute) {
                        if (in_array($attribute->getName(), [
                            Route::class,
                            Get::class,
                            Post::class,
                            Patch::class
                        ])) {
                            $route = $attribute->newInstance();
                            $basePath = "";
                            if (isset($controller))
                                $basePath = $controller->newInstance()->pathPrefix;
                            foreach ((is_array($route->path) ? $route->path : [$route->path]) as $path) {
                                $this->addRoute($basePath . $path, $route->method, $method->getClosure($method->isStatic() ? null : $object));
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }

    public function get(string $route, callable|string $callable): Router {
        return $this->addRoute($route, 'GET', $callable);
    }

    public function post(string $route, callable|string $callable): Router {
        return $this->addRoute($route, 'POST', $callable);
    }

    public function put(string $route, callable|string $callable): Router {
        return $this->addRoute($route, 'PUT', $callable);
    }

    public function patch(string $route, callable|string $callable): Router {
        return $this->addRoute($route, 'PATCH', $callable);
    }

    public function delete(string $route, callable|string $callable): Router {
        return $this->addRoute($route, 'DELETE', $callable);
    }

    public function notFound(callable|string $notFound): Router {
        $this->notFound = $notFound;
        return $this;
    }

    public function before(string $route, callable $callable): Router {
        $this->beforeInterceptor[$this->createRegex($route)] = $callable;
        return $this;
    }

    public function setIncludeDirectory(string $includeDirectory): Router {
        $this->includeDirectory = $includeDirectory;
        return $this;
    }

    public function setMatchProcessor(Closure $matchProcessor): Router {
        $this->matchProcessor = $matchProcessor;
        return $this;
    }

    public function setInstantMatches(bool $instantMatches): Router {
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
