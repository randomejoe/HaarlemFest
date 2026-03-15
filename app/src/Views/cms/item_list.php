<?php use App\Models\CmsType; ?>
<div>
    <?php include __DIR__ . '/../partials/cms/cms_nav.php';?>
    <div class='horizontal-center vertical-center'>
        <div class='cms-container'>
            <?php 
                if (isset($_SESSION['create_success']) && isset($_SESSION['create_title'])) {
                    if ($_SESSION['create_success']) {
                        $message = 'Successfully added ' . $type->value . ' ' . $_SESSION['create_title'];
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
            <div class='between row'>
                <button id='add-item-btn' class='add-item-btn button'><p>Add <?php if (isset($currentCategory) || $type != CmsType::Event) {echo $type->value;} else {
                    echo 'main event';
                } ?></p></button>
                <?php 
                    if (isset($categories)) {
                        require __DIR__ . '/../partials/cms/event_category_selector.php';
                    }
                ?>
            </div>
            <form id='add-item-form' style='display: none' class='add-item-form column' method="POST" action="<?php echo "/cms/" . $type->value; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <div>
                    <label for="item_name"><?php if (isset($currentCategory) || $type != CmsType::Event) {echo ucfirst($type->value);} else {
                    echo 'Main event';
                    } ?> name:</label>
                    <input type="text" id="item_name" name="item_name" class="form-input" required>
                </div>
                <?php if ($type == CmsType::Page) {
                    ?> 
                        <div class="vertical-center">
                            <label for="main_event">Is main event:</label>
                            <input type="checkbox" id="main_event" name="is_main_event"> 
                        </div>
                    <?php
                }
                ?>
                <button type="submit" class="form-submit-button button"><p>Create</p></button>
            </form>
            <div class='cms-item-container column'>
            <?php 
            $itemType = 'page';
            foreach ($items as $item) {
                $itemName = $item['item_name'];
                $itemId = $item['item_id'];
                if ($type == CmsType::Event) {
                    $extraFields = 'cms_item_event_fields.php';
                }
                else if ($type == CmsType::Page) {
                    $extraFields = 'cms_item_page_fields.php';
                }
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
    form.style.display = form.style.display === 'none' ? 'flex' : 'none';
    });
</script>
