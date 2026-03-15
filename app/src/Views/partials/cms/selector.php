<select class="half-width form-input" name="<?php echo $fieldName; ?>">
    <?php foreach ($items as $index => $item): ?>
        <option class="<?php if ($index == array_key_last($items)) { 
            echo "form-input category-selector-item category-selector-item-last";
            } else {
                echo "form-input category-selector-item";
            }?>" <?php if ($item == $initialSelection) {
                echo "selected";
            } ?>
            ><?php echo $item; ?></option>
    <?php endforeach; ?>
</select>