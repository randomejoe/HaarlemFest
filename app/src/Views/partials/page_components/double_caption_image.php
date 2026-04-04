<div class="d-flex flex-row gap-4 justify-content-evenly <?php if (isset($data['has_horizontal_padding']) && $data['has_horizontal_padding'] == "1") {echo 'padding-horizontal ';} ?>">
    <div class="w-40 d-flex flex-column justify-content-center">
        <img src="/images/<?php echo $data['left_image_source']; ?>">
        <p class="text-center"><?php echo $data['left_image_caption']; ?></p>
    </div>
    <div class="w-40 d-flex flex-column justify-content-center">
        <img src="/images/<?php echo $data['right_image_source']; ?>">
        <p class="text-center"><?php echo $data['right_image_caption']; ?></p>
    </div>
</div>