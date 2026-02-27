<div>
    <?php include __DIR__ . '/../partials/cms/cms_nav.php';
    ?>
    <div class='horizontal-center vertical-center'>
        <div class='cms-container'>
            <div class='cms-item-container'>
                <form method="POST">
                    <div class="row">
                        <input type="text" id="name" name="name" class="form-input" value="<?php echo $item['item_name'] ?>" required>
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