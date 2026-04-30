<?php use App\Models\CmsType; ?>
<div>
    <?php include __DIR__ . '/../partials/cms/cms_nav.php';?>
    <div class='horizontal-center vertical-center'>
        <div class='cms-container'>
            <?php 
                print_r($flash);
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
                <?php if ($type != CmsType::User && $type != CmsType::Order) {?>
                <button id='add-item-btn' class='add-item-btn button'><p>Add <?php if (isset($currentCategory) || $type != CmsType::Event) {echo $type->value;} else {
                    echo 'main event';
                } ?></p></button>
                <?php }
                    if (isset($categories)) {
                        require __DIR__ . '/../partials/cms/event_category_selector.php';
                    }
                ?>
            </div>
            <form id='add-item-form' style='display: none' class='add-item-form column' method="POST">
                <div class="row">
                    <div>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="vertical-center form-input-container">
                            <label for="item_name"><?php if (isset($currentCategory) || $type != CmsType::Event) {echo ucfirst($type->value);} else {
                            echo 'Main event';
                            } ?> name:</label>
                            <input type="text" id="item_name" name="item_name" class="form-input" required>
                        </div>
                        <?php 
                        if ($type == CmsType::Page) {
                            ?> 
                                <div class="vertical-center">
                                    <label for="main_event">Is main event:</label>
                                    <input type="checkbox" id="main_event" name="is_main_event" class="checkbox-input"> 
                                </div>
                            <?php
                        }
                        else if (isset($currentCategory)) {
                        ?>
                        <div class="vertical-center form-input-container">
                            <label for="start_time">Start time:</label>
                            <input type="datetime-local" id="start_time" name="start_time" class="form-input" required> 
                        </div>
                        <div class="vertical-center form-input-container">
                            <label for="end_time">End time:</label>
                            <input type="datetime-local" id="end_time" name="end_time" class="form-input" required> 
                        </div>
                        <div class="vertical-center form-input-container">
                            <label for="language">Language:</label>
                            <input type="text" id="language" name="language" class="form-input"> 
                        </div>
                    </div>
                    <div>
                        <!-- TODO: change to selector for location -->
                        <div class="vertical-center form-input-container">
                            <label for="location">Location:</label>
                            <input type="text" id="location" name="location" class="form-input" required> 
                        </div>
                        <!-- TODO: change to use selected location ticket count -->
                        <div class="vertical-center form-input-container">
                            <label for="amount">Ticket amount:</label>
                            <input type="number" id="amount" name="ticket_amount" class="form-input" min="0" required> 
                        </div>
                        <div class="vertical-center form-input-container">
                            <label for="price">Ticket price:</label>
                            <input type="number" id="price" name="ticket_price" class="form-input" step="0.01" min="0" required> 
                        </div>
                    <?php
                    }
                    ?>
                    </div>
                </div>
                <?php 
                if (isset($currentCategory)) {
                    ?>
                        <div class="vertical-center form-input-container description-container">
                            <label for="description">Description:</label>
                            <input type="text" id="description" name="description" class="form-input half-width"> 
                        </div>
                    <?php
                } 
                ?>
                <button type="submit" class="form-submit-button button"><p>Create</p></button>
            </form>
            <div class='cms-item-container column'>
            <?php 
            $itemType = $type->value;
            foreach ($items as $item) {
                if ($type == CmsType::Event) {
                    $extraFields = 'cms_item_event_fields.php';
                }
                else if ($type == CmsType::Page) {
                    $extraFields = 'cms_item_page_fields.php';
                }
                else if ($type == CmsType::User) {
                    $extraFields = 'cms_item_user_fields.php';
                }
                else if ($type == CmsType::Order) {
                    $extraFields = 'cms_item_order_fields.php';
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
    const start = document.getElementById("start_time");
    const end = document.getElementById("end_time");

    btn.addEventListener('click', () => {
    form.style.display = form.style.display === 'none' ? 'flex' : 'none';
    });

    form.addEventListener('submit', (e) => {
        if (new Date(end.value) < new Date(start.value)) {
            e.preventDefault();
            alert('temporary alert to notify that event starts after it ends')
        }
    })
</script>
