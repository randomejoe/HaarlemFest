<div>
    <?php include __DIR__ . '/../partials/cms/cms_nav.php';?>
    <div class='horizontal-center vertical-center'>
        <div class='cms-container'>
            <?php 
                if (isset($_SESSION['create_success']) && isset($_SESSION['create_title'])) {
                    if ($_SESSION['create_success']) {
                        $message = 'Successfully added page ' . $_SESSION['create_title'];
                        $notification_type = 'success';
                    }
                    else {
                        $message = 'Failed to add page ' . $_SESSION['create_title'];
                        $notification_type = 'failure';
                    }
                    require __DIR__ . '/../partials/cms/notification.php';
                    unset($_SESSION['create_success']);
                    unset($_SESSION['create_title']);
                }
            ?>
            <button id='add-item-btn' class='add-item-btn button'><p>Add page</p></button>
            <form id='add-item-form' style='display: none' class='add-item-form' method="POST" action="/cms/pages">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <label for="title">Page Name:</label>
                <input type="text" id="title" name="title" class="form-input" required>
                <button type="submit" class="form-submit-button button">Create</button>
            </form>
            <div class='cms-item-container column'>
            <?php 
            $itemType = 'page';
            foreach ($pages as $page) {
                $itemName = $page['title'];
                $itemId = $page['id'];
                require __DIR__ . '/../partials/cms/cms_item.php';
            } ?>
            </div>
        </div>
    </div>
</div>

<script>
    const btn = document.getElementById('add-item-btn');
    const form = document.getElementById('add-item-form');

    btn.addEventListener('click', () => {
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    });
</script>
