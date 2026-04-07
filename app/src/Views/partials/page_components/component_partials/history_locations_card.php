<div class="history-location-card d-flex justify-content-between <?php echo $index % 2 == 0 ? 'flex-row' : 'flex-row-reverse'?>">
    <div class="d-flex w-100 <?php echo $index % 2 == 0 ? 'left' : 'right flex-grow-1 justify-content-between'?>">
        <img src='<?php echo '/images/' . $location->getImage() ?>'>
        <div class="px-4 w-100">
            <div>
                <h3> <?php echo $location->getName() ?></h3>
                <?php echo $location->getDescription(); ?>
            </div>
            <div class="d-flex history-location-card-learn-more <?php echo $index % 2 == 0 ? 'right' : 'left'?>">
                <a href="/<?php echo $location->getName(); ?>">Learn more</a>
            </div>
        </div>
    </div>
</div>