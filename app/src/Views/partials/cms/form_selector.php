<div class="half-width">
    <?php if (isset($labelText)) {
        ?>
        <label for="<?php echo $fieldName; ?>"><?php echo $labelText;?>:</label>
        <?php
    }
    ?>
    <select class="half-width form-input" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>">
        <?php foreach ($selectorItems as $index => $selectorItem): ?>
            <option class="<?php if ($index == array_key_last($selectorItems)) { 
                echo "form-input category-selector-item category-selector-item-last";
                } else {
                    echo "form-input category-selector-item";
                }?>" <?php if ($selectorItem == $initialSelection) {
                    echo "selected";
                } ?>
                ><?php echo $selectorItem; ?></option>
        <?php endforeach; ?>
    </select>
</div>
