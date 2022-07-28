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
use de\interaapps\ulole\router\attributes\methods\Put;
use de\interaapps\ulole\router\attributes\QueryParam;
use de\interaapps\ulole\router\attributes\Route;
use de\interaapps\ulole\router\attributes\RouteVar;
use de\interaapps\ulole\router\attributes\With;
use de\interaapps\ulole\router\interfaces\ExceptionHandler;
use de\interaapps\ulole\router\interfaces\Middleware;
use de\interaapps\ulole\router\interfaces\RequestHandler;
use de\interaapps\ulole\router\transformers\JSONResponseTransformer;
use de\interaapps\ulole\router\transformers\ResponseTransformer;
use Exception;
use ReflectionClass;
use ReflectionFunction;

class Router {
    private array $routes;
    private string $includeDirectory;
    private mixed $notFound;
    private array $beforeInterceptor;
    private Closure|null $exceptionHandler = null;
    private array $middlewares = [];

    private array $responseTransformers = [];

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
        $this->jsonPlus = JSONPlus::createDefault();
    }

    public function run(bool $showNotFound = true): bool {
        if ($this->instantMatches && $this->hasInstantMatch) return true;

        $requestMethod = $_SERVER['REQUEST_METHOD'];

        $body = "";
        if (defined('STDIN'))
            $body = stream_get_contents(STDIN);

        $request = new Request($this, $body);
        $response = new Response($this);

        $defaultParameters = [
            "request" => [
                "type"  => "TYPE",
                "class" => Request::class,
                "value" => $request
            ],
            "response" => [
                "type"  => "TYPE",
                "class" => Response::class,
                "value" => $response
            ],
            "router" => [
                "type"  => "TYPE",
                "class" => Router::class,
                "value" => $this
            ]
        ];

        foreach ($this->routes as $path => $route) {
            $matches = $this->matches($path);

            if ($matches !== false && isset($route[$requestMethod])) {
                $request->setRouteVars($matches["routeVars"]);

                $currentRoute = $route[$requestMethod];

                $intercepted = false;

                foreach ($this->beforeInterceptor as $interceptorPath => $beforeInterceptorCallable) {
                    $interceptorMatches = $this->matches($interceptorPath);
                    if ($interceptorMatches !== false) {
                        $interceptorResult = $this->invoke($beforeInterceptorCallable, $defaultParameters);
                        if ($interceptorResult !== null)
                            $intercepted = $interceptorResult;
                    }
                }

                if (!$intercepted) {
                    foreach ($matches['routeVars'] as $name => $value) {
                        $defaultParameters[] = [
                            "type"  => "NAME",
                            "name"  => $name,
                            "value" => $value
                        ];
                    }

                    foreach ($this->middlewares as $name => $middleware) {
                        if (in_array($name, $currentRoute["middlewares"]))
                            $this->invoke($middleware, $defaultParameters);
                    }

                    echo $this->transformResponse($request, $response, $this->invoke($currentRoute["callable"], $defaultParameters));

                    return true;
                }
            }
        }
        if (!$showNotFound)
            return false;

        // Page not found
        header('HTTP/1.1 404 Not Found');
        if ($this->notFound !== null) {
            echo $this->transformResponse($request, $response, $this->invoke($this->notFound, $defaultParameters));
        } else
            echo "Page not found";
        return false;
    }

    private function transformResponse(Request $req, Response $res, mixed $body) : mixed {
        foreach ($this->responseTransformers as $responseTransformer) {
            $r = $responseTransformer($req, $res, $body);
            if ($r !== null) {
                return $r;
            }
        }
        return $body;
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

    /**
     * @throws Null
     */
    public function invoke(callable|string $callable, array $params = [], $exceptionHandling = true) {
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

            try {
                return $refl->invokeArgs($funcParams);
            } catch (Exception $e) {
                if ($this->exceptionHandler !== null && $exceptionHandling) {
                    return $this->invoke($this->exceptionHandler, array_merge($params, [[
                        "type"  =>"TYPE",
                        "class" => Exception::class,
                        "value" => $e
                    ]]), false);
                } else {
                    throw new $e;
                }
            }
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

    public function addRoute(string $route, string $methods, callable|RequestHandler|string $callable, array $middlewares = []): Router {
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

        foreach (explode("|", $methods) as $method) {
            $this->routes[$route][$method] = [
                "callable" => $callable instanceof RequestHandler ? $callable->handle(...) : $callable,
                "middlewares" => $middlewares
            ];
        }

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

            $controllerAttribs = $clazz->getAttributes(With::class);

            foreach ($controllers as $controller) {

                foreach ($clazz->getMethods() as $method) {
                    foreach ($method->getAttributes() as $attribute) {
                        if (in_array($attribute->getName(), [
                            Route::class,
                            Get::class,
                            Post::class,
                            Patch::class,
                            Put::class,
                        ])) {
                            $route = $attribute->newInstance();
                            $basePath = "";
                            if (isset($controller))
                                $basePath = $controller->newInstance()->pathPrefix;

                            $middlewares = array_merge(...array_map(fn(\ReflectionAttribute $attrib) => $attrib->newInstance()->name, array_merge($method->getAttributes(With::class), $controllerAttribs)));

                            foreach ((is_array($route->path) ? $route->path : [$route->path]) as $path) {
                                $this->addRoute($basePath . $path, $route->method, $method->getClosure($method->isStatic() ? null : $object), $middlewares);
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }

    public function get(string $route, callable|RequestHandler|string $callable, array $middlewares = []): Router {
        return $this->addRoute($route, 'GET', $callable, $middlewares);
    }

    public function post(string $route, callable|RequestHandler|string $callable, array $middlewares = []): Router {
        return $this->addRoute($route, 'POST', $callable, $middlewares);
    }

    public function put(string $route, callable|RequestHandler|string $callable, array $middlewares = []): Router {
        return $this->addRoute($route, 'PUT', $callable, $middlewares);
    }

    public function patch(string $route, callable|RequestHandler|string $callable, array $middlewares = []): Router {
        return $this->addRoute($route, 'PATCH', $callable, $middlewares);
    }

    public function delete(string $route, callable|RequestHandler|string $callable, array $middlewares = []): Router {
        return $this->addRoute($route, 'DELETE', $callable, $middlewares);
    }

    public function notFound(callable|RequestHandler|string $notFound): Router {
        $this->notFound = $notFound instanceof RequestHandler ? $notFound->handle(...) : $notFound;
        return $this;
    }

    public function before(string $route, callable|RequestHandler $callable): Router {
        $this->beforeInterceptor[$this->createRegex($route)] = $callable instanceof RequestHandler ? $callable->handle(...) : $callable;
        return $this;
    }

    public function setIncludeDirectory(string $includeDirectory): Router {
        $this->includeDirectory = $includeDirectory;
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

    public function exceptionHandler(Closure|ExceptionHandler $exceptionHandler): Router {
        $this->exceptionHandler = $exceptionHandler instanceof ExceptionHandler ? $exceptionHandler->handle(...) : $exceptionHandler;
        return $this;
    }

    public function middleware(string $name, Closure|Middleware $callable): Router {
        $this->middlewares[$name] = $callable instanceof Middleware ? $callable->handle(...) : $callable;
        return $this;
    }

    public function getResponseTransformers(): array {
        return $this->responseTransformers;
    }

    public function setResponseTransformers(array $responseTransformers): Router {
        $this->responseTransformers = $responseTransformers;
        return $this;
    }

    public function responseTransformer(callable|ResponseTransformer $responseTransformer): Router {
        $this->responseTransformers[] = $responseTransformer instanceof ResponseTransformer ? $responseTransformer->transform(...) : $responseTransformer;
        return $this;
    }

    /**
     * @param bool $transformAll Should strings be transformed as well?
     * @return $this
     */
    public function jsonResponseTransformer(bool $transformAll = false) : Router {
        $this->responseTransformer(new JSONResponseTransformer($this, $transformAll));
        return $this;
    }
}
