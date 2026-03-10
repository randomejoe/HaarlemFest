<div class='cms-item'>
    <div class='cms-item-content vertical-center'>
        <?php echo $itemName; ?>
        <div class='cms-item-buttons vertical-center'>
            <a class='edit-btn button' href=<?php echo '/cms/' . $itemType . 's/' . $itemId . '/edit'?>>
                <p>Edit <?php echo $itemType ?></p>
            </a>
            <form method="POST" class="no-margin" action=<?php echo '/cms/' . $itemType . 's/' . $itemId . '/delete'?>>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="return_url" value="<?php echo $_SERVER['REQUEST_URI'] ?>">
                <button type="submit" class='delete-btn button'>
                    <p><?php 
                    if (isset($remove) && $remove == true) 
                    { 
                        echo 'Remove ';
                    } else { 
                        echo 'Delete ';
                    } 
                    echo $itemType ?></p>
                </button>
            </form>
        </div>
        
    </div>
</div>