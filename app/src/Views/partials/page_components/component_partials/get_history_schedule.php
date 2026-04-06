<?php
$events = $eventService->getAllInCategory("A stroll through history");
$schedule = [];

foreach ($events as $event) {
    $date = date('Y-m-d', strtotime($event->startTime()));
    $time = date('H:i', strtotime($event->startTime()));
    $language = $event->getLanguage();
    $schedule[$date][$time][$language->value][] = $event;
}
?>