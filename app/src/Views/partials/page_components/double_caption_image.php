<div class="d-flex justify-content-evenly double-caption-image <?php if (isset($data['has_horizontal_padding']) && $data['has_horizontal_padding'] == "1") {echo 'padding-horizontal ';} ?>">
    <div class="d-flex flex-column justify-content-center">
        <img src="/images/<?php echo $data['left_image_source']; ?>">
        <p class="text-center"><?php echo $data['left_image_caption']; ?></p>
    </div>
    <div class="d-flex flex-column justify-content-center">
        <img src="/images/<?php echo $data['right_image_source']; ?>">
        <p class="text-center"><?php echo $data['right_image_caption']; ?></p>
    </div>
</div>