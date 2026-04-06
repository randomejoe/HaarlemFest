<div class="wysiwyg padding-horizontal">
    <h2 class="wysiwyg"><?php echo $data['header_text'] ?? '' ?></h2>
    <?php require __DIR__ . '/component_partials/get_history_schedule.php'; ?>
    <div class="history-schedule-container">
        <?php
            foreach ($schedule as $date => $scheduleDay) {
                require __DIR__ . '/component_partials/history_schedule_card_container.php';
            }
        ?>
    </div>
</div>