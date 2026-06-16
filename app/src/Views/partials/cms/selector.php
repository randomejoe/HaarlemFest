<div>
    <button class="button form-input selector" id="open-selector-btn" type="button">
        <?= ($currentOption ?? 'None') != "" ? $currentOption ?? 'None' : 'None' ?>
    </button>
    <div class="column selector-item-container" style="position: absolute; display: none" id="options">
        <?php 
        if ($hasNone) 
        {
            ?> 
                <button 
                    type="button"
                    class="selector-item form-input"
                    data-param="<?= $param ?>"
                    data-value="">
                    None
                </button>
            <?php 
        } 
        foreach ($options as $option => $paramValue): ?>
            <button 
                type="button"
                class="selector-item form-input <?= ($option == array_key_last($options)) ? 'selector-item-last' : '' ?>"
                data-param="<?= $param ?>"
                data-value="<?= $paramValue ?>">
                <?= $option ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<script src="/js/cms_selector.js"></script>