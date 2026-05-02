<?php

namespace App\Models;
use App\Models\CmsItem;

class Page extends CmsItem
{
    public function __construct(
        private int $id,
        private string $title,
        private bool $isMainEvent,
        private array $content,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['page_id'] ?? 0),
            title: (string) ($data['title'] ?? ''),
            isMainEvent: (bool) ($data['is_main_event'] ?? false),
            content: (array) ($data['content'] ?? []),
        );
    }

    public function getId(): int {
        return $this->id;
    }
    public function getName(): string {
        return $this->title;
    }
    public function getIsMainEvent(): bool {
        return $this->isMainEvent;
    }
    public function getContent(): array {
        return $this->content;
    }
    public function setContent(array $content) {
        $this->content = $content;
    }
}
