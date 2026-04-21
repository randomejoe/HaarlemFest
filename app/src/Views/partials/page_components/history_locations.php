<div class="wysiwyg padding-horizontal <?php if (isset($data['has_top_padding']) && $data['has_top_padding'] == "1") {echo 'padding-top ';}?>">
    <h2 class="wysiwyg"><?php echo $data['header_text'] ?? '' ?></h2>
    <?php 
    $locations = $data['locations'];

    ?>
    <div class="gap-3 d-flex flex-column">
        <?php
            foreach ($locations as $index => $location) {
                require __DIR__ . '/component_partials/history_locations_card.php';
            }
        ?>
    </div>
</div>
