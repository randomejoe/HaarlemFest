<div class='cms-item'>
    <div class='cms-item-content vertical-center'>
        <?php echo $itemName; ?>
        <div class='cms-item-buttons vertical-center'>
            <a class='edit-btn button' href=<?php echo '/cms/' . $itemType . 's/' . $itemId . '/edit'?>>
                <p>Edit <?php echo $itemType ?></p>
            </a>
            <a class='delete-btn button'>
                <p><?php 
                if (isset($remove) && $remove == true) 
                { 
                    echo 'Remove ';
                } else { 
                    echo 'Delete ';
                } 
                echo $itemType ?></p>
            </a>
        </div>
        
    </div>
</div>