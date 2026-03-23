<h2 class="wysiwyg"><?php echo $data['header_text'] ?? '' ?></h2>
<?php 
$events = $eventService->getAllInCategory("A stroll through history");
$schedule = [];

foreach ($events as $event) {
    $date = date('Y-m-d', strtotime($event->startTime()));
    $time = date('H:i', strtotime($event->startTime()));
    $language = $event->getLanguage();
    $schedule[$date][$time][$language->value] = ($schedule[$date][$time][$language->value] ?? 0) + 1;
}
?><div class="history-schedule-container"><?php
foreach ($schedule as $date => $scheduleDay) {
    require __DIR__ . '/component_partials/history_schedule_card_container.php';
}
?>
 </div>