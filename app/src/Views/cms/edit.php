<div>
    <?php include __DIR__ . '/../partials/cms/cms_nav.php';
    ?>
    <div class='horizontal-center vertical-center'>
        <div class='cms-container'>
            <div class='cms-item-container'>
                <form method="POST">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($item['item_name'], ENT_QUOTES) ?>" required>
                    <button type="submit">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>