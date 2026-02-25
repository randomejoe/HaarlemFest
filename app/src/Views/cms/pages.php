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
            <button id='add-cms-item-btn' class='add-cms-item-btn button'><p>Add page</p></button>
            <form id='add-cms-item-form' style='display: none' class='add-cms-item-form' method="POST">
                <label for="title">Page Name:</label>
                <input type="text" id="title" name="title" required>
                <button type="submit">Create</button>
            </form>
            <div class='cms-item-container'>
            <?php 
            $item_type = 'page';
            foreach ($pages as $page) {
                $name = $page['title'];
                $id = $page['id'];
                require __DIR__ . '/../partials/cms/cms_item.php';
            } ?>
            </div>
        </div>
    </div>
</div>

<script>
    const btn = document.getElementById('add-cms-item-btn');
    const form = document.getElementById('add-cms-item-form');

    btn.addEventListener('click', () => {
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    });
</script>