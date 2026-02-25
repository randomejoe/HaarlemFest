<div>
    <?php include __DIR__ . '/../partials/cms/cms_nav.php';
    ?>
    <div class='horizontal-center vertical-center'>
        <div class='cms-container'>
            <div class='cms-item-container'>
                <form method="POST">
                    <input type="text" id="name" name="name" value="<?php echo $item['item_name'] ?>" required>
                    <button type="submit">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>