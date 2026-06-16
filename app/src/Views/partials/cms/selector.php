<div>
    <button class="button form-input selector" id="open-selector-btn" type="button">
        <?= $currentOption ?? 'None' ?>
    </button>
    <div class="column selector-item-container" style="position: absolute; display: none" id="options">
        <?php 
        if ($hasNone) 
        {
            ?> 
                <a href='' class="form-input selector-item">None</a> 
            <?php 
        } 
        foreach ($options as $option => $routeInfo): ?>
            <button 
                type="button"
                class="selector-item form-input <?= ($option == array_key_last($options)) ? 'selector-item-last' : '' ?>"
                data-param="<?= $routeInfo["param"] ?>"
                data-value="<?= $routeInfo["value"] ?>">
                <?= $option ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<script src="/js/cms_selector.js"></script>