<?php

namespace App\Models;
use App\Models\CmsItem;

class Location extends CmsItem
{
    public function __construct(
        private int $id,
        private string $name,
        private string $description,
        private string $image,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['location_id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            image: (string) ($data['image'] ?? ''),
        );
    }

    public function getId(): int {
        return $this->id;
    }
    public function getName(): string {
        return $this->name;
    }
    public function getDescription(): string {
        return $this->description;
    }
    public function getImage(): string {
        return $this->image;
    }
}
