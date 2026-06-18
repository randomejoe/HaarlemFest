<?php

namespace App\Models;

abstract class CmsItem
{
    public function __construct(
    ) {
    }

    abstract static function fromArray(array $data): self;

    abstract public function getId(): int;
    abstract public function getName(): string;
}
