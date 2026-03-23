<div class="history-location-card d-flex flex-row justify-content-between">
    <div class="d-flex flex-row">
        <img src='<?php echo '/images/' . $location->getImage() ?>' width="200">
        <p class="h-100"><?php echo $location->getDescription(); ?></p>
    </div>
    <a href="/<?php echo $location->getName(); ?>">Learn more</a>
</div>