<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Event;
use App\ViewModels\EventCardViewModel;

interface IEventService
{
    public function getForEdit(int $id): ?Event;

    public function isNameEditable(): bool;

    public function updateWithImage(int $id, array $postData, array $fileData): bool;

    public function update(int $id, array $postData);

    public function delete(int $id): bool;

    public function getAll(array $params);

    public function getAllInCategory(string $category);

    public function getSchedule(string $event): array;

    public function findById(int $eventId): ?Event;

    public function findByName(string $eventName): array;

    public function getArtistScheduleData(string $artistName): EventCardViewModel;

    public function getArtistVenuesData(string $artistName): EventCardViewModel;

    public function getLineupDataForCategory(string $category): EventCardViewModel;

    public function getProgramDataForCategory(string $category): EventCardViewModel;

    public function getCategories();

    public function create(array $postData);

    public function createForCategory(string $category, array $postData);
}
