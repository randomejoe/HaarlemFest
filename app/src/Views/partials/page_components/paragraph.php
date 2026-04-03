<div class="wysiwyg <?php if (isset($data['has_horizontal_padding']) && $data['has_horizontal_padding'] == "1") {echo 'padding-horizontal ';} ?>">
    <h2 class="wysiwyg"><?php echo $data['header_text'] ?? '' ?></h2>
    <p class="wysiwyg"><?php echo $data['paragraph_text'] ?? '' ?></p>
</div>