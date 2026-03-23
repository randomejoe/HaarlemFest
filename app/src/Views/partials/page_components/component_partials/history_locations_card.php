<div class="history-location-card d-flex justify-content-between <?php echo $index % 2 == 0 ? 'flex-row' : 'flex-row-reverse'?>">
    <div class="d-flex <?php echo $index % 2 == 0 ? 'flex-row' : 'flex-row-reverse flex-grow-1 justify-content-between'?>">
        <img src='<?php echo '/images/' . $location->getImage() ?>' width="200">
        <div>
            <h3> <?php echo $location->getName() ?></h3>
            <?php echo $location->getDescription(); ?>
        </div>
    </div>
    <a href="/<?php echo $location->getName(); ?>">Learn more</a>
</div>