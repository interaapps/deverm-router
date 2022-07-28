<?php
namespace de\interaapps\ulole\router\transformers;

use de\interaapps\ulole\router\Request;
use de\interaapps\ulole\router\Response;
use de\interaapps\ulole\router\Router;

class JSONResponseTransformer implements ResponseTransformer {

    public function __construct(
        private readonly Router $router,
        private readonly bool $transformAll = false
    ) {
    }

    public function transform(Request $req, Response $res, mixed $body): mixed {
        if ($this->transformAll || is_array($body) || is_object($body) || is_bool($body)) {
            $res->setContentType('application/json');
            return $this->router->getJsonPlus()->toJson($body);
        }
        return null;
    }
}