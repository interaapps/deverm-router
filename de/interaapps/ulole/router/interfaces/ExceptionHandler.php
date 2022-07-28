<?php

namespace de\interaapps\ulole\router\interfaces;

use de\interaapps\ulole\router\Request;
use de\interaapps\ulole\router\Response;
use Exception;

interface ExceptionHandler {
    public function handle(Exception $exception, Request $req, Response $res) : mixed;
}