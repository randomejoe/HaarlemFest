<div class="history-location-card d-flex justify-content-between <?php echo $index % 2 == 0 ? 'flex-row' : 'flex-row-reversed'?>">
    <div class="d-flex flex-row">
        <img src='<?php echo '/images/' . $location->getImage() ?>' width="200">
        <p class="h-100"><?php echo $location->getDescription(); ?></p>
    </div>
    <a href="/<?php echo $location->getName(); ?>">Learn more</a>
</div>