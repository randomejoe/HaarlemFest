<div class="wysiwyg padding-horizontal">
    <h2 class="wysiwyg"><?php echo $data['header_text'] ?? '' ?></h2>
    <div class="history-schedule-container">
        <?php
            foreach ($data['schedule'] as $date => $scheduleDay) {
                require __DIR__ . '/component_partials/history_schedule_card_container.php';
            }
        ?>
    </div>
</div>