<?php

namespace App\Repositories\Interfaces;

use App\Models\Event;

interface IEventRepository
{
    public function findById(int $eventId): ?Event;

    public function findByCategory(string $category): array;

    public function findByName(string $eventName): array;

    public function findVenuesByArtist(string $artistName): array;

    /**
     * @param  array<int,int> $eventIds
     * @return array<int, Event>
     */
    public function findByIds(array $eventIds): array;

    /**
     * @param  array<int,int> $eventIds
     * @return array<int, array{event_id:int,name:string,ticket_amount:int}>
     */
    public function findStockByIds(array $eventIds): array;

    public function decrementTicketAmountIfAvailable(int $eventId, int $quantity): bool;

    public function incrementTicketAmount(int $eventId, int $quantity): void;

    public function getAllEvents(): array;

    public function getAllEventsInCategory(string $category): array;

    public function createSubEvent(string $category, array $postData);

    public function getEventForEdit(int $id): Event;

    public function updateEvent(int $id, array $postData);

    public function deleteEvent(int $id);
}
