<?php

namespace de\interaapps\ulole\router\attributes;

#[\Attribute]
class Controller {
    public function __construct(
        public string $pathPrefix = "") {
    }
}