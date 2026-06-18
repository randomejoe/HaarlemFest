<div class="wysiwyg d-flex history-detailpage-top g-0">
    <div class="flex-grow-1 text">
        <h1 class="wysiwyg">
        <?php echo $data['title_text'] ?? '' ?>
        </h1>
        <h2 class="wysiwyg"><?php echo $data['header_text'] ?? '' ?></h2>
        <p class="wysiwyg"><?php echo $data['paragraph_text'] ?? '' ?></p>
    </div>
    <div class="gap-4 d-flex images justify-content-evenly">
        <img src="/images/<?php echo $data['map_image_source']; ?>">
        <div>
            <img src="/images/<?php echo $data['night_image_source']; ?>">
            <p class="text-center"><?php echo $data['night_image_caption'];?></p>
        </div>
    </div>
</div>