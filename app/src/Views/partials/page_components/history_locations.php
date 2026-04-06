<div class="wysiwyg padding-horizontal">
    <h2 class="wysiwyg"><?php echo $data['header_text'] ?? '' ?></h2>
    <?php 
    $locations = $locationService->getAll();

    ?>
    <div class="gap-3 d-flex flex-column">
        <?php
            foreach ($locations as $index => $location) {
                require __DIR__ . '/component_partials/history_locations_card.php';
            }
        ?>
    </div>
</div>
