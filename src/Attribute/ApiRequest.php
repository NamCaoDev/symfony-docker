<?php

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ApiRequest
{
    public function __construct(
        public bool $validate = true,
        public bool $deserialize = true
    ) {}
}
