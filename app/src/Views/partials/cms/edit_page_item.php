<div class="cms-item between row vertical-center"><?php 

echo $contentItem['component_name'];

?><div class="row vertical-center"><?php
foreach ($contentItem['variables'] as $variable) {
    $name = 'components[' . $contentItem['id'] . '][' . $variable['id'] . ']';
    ?>
    
    <label for=<?= $name; ?>><?php echo $variable['key'] ?> value:</label>
    <input type="text" id=<?= $name; ?> name=<?= $name; ?> class="form-input" size="5" value=<?php echo $variable['value'] ?>>
    <?php
}?>
</div></div>