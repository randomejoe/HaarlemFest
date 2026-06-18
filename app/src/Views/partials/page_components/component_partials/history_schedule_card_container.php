<div class="history-schedule-day"> <?php 
?><div class="horizontal-center"><h3 class="my-2"><?php echo date('F jS', strtotime($date)); ?></h3></div>
<div class="history-schedule-card-container"><?php
foreach ($scheduleDay as $time => $scheduleTime) {
    require __DIR__ . '/history_schedule_card.php';
}
?>
</div>
</div>