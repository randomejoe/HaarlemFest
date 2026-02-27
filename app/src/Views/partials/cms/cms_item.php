<div class='cms-item'>
    <div class='cms-item-content vertical-center'>
        <?php echo $name; ?>
        <div class='cms-item-buttons vertical-center'>
            <a class='edit-btn button' href=<?php echo '/cms/' . $item_type . 's/' . $id . '/edit'?>>
                <p>Edit <?php echo $item_type ?></p>
            </a>
            <a class='delete-btn button'>
                <p>Delete <?php echo $item_type ?></p>
            </a>
        </div>
        
    </div>
</div>