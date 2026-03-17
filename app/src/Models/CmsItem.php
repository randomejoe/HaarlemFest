<?php

namespace App\Models;

abstract class CmsItem
{
    public function __construct(
    ) {
    }

    abstract static function fromArray(array $data): self;

    abstract public function getId();
    abstract public function getName();
}
