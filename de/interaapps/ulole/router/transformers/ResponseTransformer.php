<?php

namespace de\interaapps\ulole\router\transformers;

use de\interaapps\ulole\router\Request;
use de\interaapps\ulole\router\Response;

interface ResponseTransformer {
    public function transform(Request $req, Response $res, mixed $body) : mixed;
}