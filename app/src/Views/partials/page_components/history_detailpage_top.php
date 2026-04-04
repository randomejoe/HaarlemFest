<div class="wysiwyg d-flex row g-0 gap-4 <?php if (isset($data['has_top_padding']) && $data['has_top_padding'] == "1") {echo 'padding-top ';} if (isset($data['has_horizontal_padding']) && $data['has_horizontal_padding'] == "1") {echo 'padding-horizontal ';} ?>">
    <div class="w-50 flex-grow-1">
        <h1 class="wysiwyg">
        <?php echo $data['title_text'] ?? '' ?>
        </h1>
        <h2 class="wysiwyg"><?php echo $data['header_text'] ?? '' ?></h2>
        <p class="wysiwyg"><?php echo $data['paragraph_text'] ?? '' ?></p>
    </div>
    <div class="w-25 gap-4 d-flex flex-column">
        <img src="/images/<?php echo $data['map_image_source']; ?>">
        <div>
            <img src="/images/<?php echo $data['night_image_source']; ?>">
            <p><?php echo $data['night_image_caption'];?></p>
        </div>
    </div>
</div>