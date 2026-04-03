<h1 class="wysiwyg <?php if (isset($data['has_top_padding']) && $data['has_top_padding'] == "1") {echo 'padding-top ';} if (isset($data['has_horizontal_padding']) && $data['has_horizontal_padding'] == "1") {echo 'padding-horizontal ';} ?>">
    <?php echo $data['text'] ?? '' ?>
</h1>