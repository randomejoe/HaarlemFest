<?php

namespace App\Models;
use App\Models\CmsItem;

class PageContent extends CmsItem
{
    public function __construct(
        private int $id,
        private string $name,
        private array $data,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['content_id'] ?? 0),
            name: (string) ($data['component_name'] ?? ''),
            data: (array) (isset($data['data']) ? json_decode($data['data'], true) : []),
        );
    }

    public function getId(): int {
        return $this->id;
    }
    public function getName(): string {
        return $this->name;
    }
    public function getData(): array {
        return $this->data;
    }
}
