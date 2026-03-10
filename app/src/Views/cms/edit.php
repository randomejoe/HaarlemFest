<!DOCTYPE html>
<div>
    <?php include __DIR__ . '/../partials/cms/cms_nav.php';
    ?>
    <div class='horizontal-center vertical-center'>
        <div class='cms-container'>
            <div class='cms-item-container column'>
                <form method="POST">
                    <div class="row">
                        <input type="text" id="name" name="name" class="form-input" value="<?php 
                        if (isset($item[0])) {
                            $firstItem = $item[0];
                        } else {
                            $firstItem = $item;
                        } 
                        echo $firstItem['item_name']
                        ?>" <?php if (!$editable) { echo 'readonly';} ?> required>
                        <button type="submit" class="form-submit-button button">Save</button>
                    </div>
                    <?php 
                        require(__DIR__ . '/../partials/cms/edit_' . $type . '.php');
                    ?>
                </form>
            </div>
        </div>
    </div>
</div>