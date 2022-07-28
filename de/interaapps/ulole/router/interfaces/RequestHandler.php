<?php

namespace de\interaapps\ulole\router\interfaces;

use de\interaapps\ulole\router\Request;
use de\interaapps\ulole\router\Response;

interface RequestHandler {
    public function handle(Request $req, Response $res) : mixed;
}