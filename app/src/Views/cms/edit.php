<!DOCTYPE html>
<?php use App\Models\CmsType; ?>
<div>
    <?php include __DIR__ . '/../partials/cms/cms_nav.php';
    ?>
    <div class='horizontal-center vertical-center'>
        <div class='cms-container'>
            <div class='cms-item-container column'>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
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
                        if ($type != CmsType::Page ) {
                            require(__DIR__ . '/../partials/cms/edit_' . $type->value . '.php');
                        }
                        ?></form><?php
                        if ($type == CmsType::Page) {
                            require(__DIR__ . '/../partials/cms/edit_' . $type->value . '.php');
                        }
                    ?>
            </div>
        </div>
    </div>
</div>