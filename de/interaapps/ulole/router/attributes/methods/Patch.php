<?php
namespace de\interaapps\ulole\router\attributes\methods;

use Attribute;
use de\interaapps\ulole\router\attributes\Route;

#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Patch extends Route {
    public function __construct(string|array $path = "") {
        $this->path = $path;
        $this->method = "PATCH";
    }
}