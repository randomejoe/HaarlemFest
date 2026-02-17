<div>
    <?php include __DIR__ . '/../components/cms_nav.php';?>
    <div class='horizontal-center vertical-center'>
        <div class='cms-container'>
            <button class='add-page-btn button'><p>Add page</p></button>
            <?php 
                if (isset($_SESSION['create_success']) && isset($_SESSION['create_title'])) {
                    if ($_SESSION['create_success']) {
                        $message = 'Successfully added page ' . $_SESSION['create_title'];
                        require __DIR__ . '/../components/success_notification.php';
                    }
                    else {
                        $message = 'Failed to add page ' . $_SESSION['create_title'];
                        require __DIR__ . '/../components/error_notification.php';
                    }
                    unset($_SESSION['create_success']);
                    unset($_SESSION['create_title']);
                }
            ?>
            <form method="POST" action="/cms/pages">
                <label for="title">Page Name:</label>
                <input type="text" id="title" name="title" required>
                <button type="submit">Create</button>
            </form>
            <div class='page-item-container'>
            <?php 
            foreach ($pages as $page) {
                $title = $page['title'];
                require __DIR__ . '/../components/page_item.php';
            } ?>
            </div>
        </div>
    </div>
</div>
