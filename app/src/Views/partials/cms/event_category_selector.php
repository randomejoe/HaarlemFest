<div>
    <button class="button form-input selector" id="open-selector-btn">
        <?php echo urldecode($currentCategory ?? 'None') ?>
    </button>
    <div class="column selector-item-container" style="position: absolute; display: none" id="categories">
        <a href='/cms/events' class="form-input selector-item">None</a>
        <?php foreach ($categories as $index => $category): ?>
            <a href='<?php echo '/cms/events/' . $category?>' class="<?php if ($index == array_key_last($categories)) { 
                echo "form-input selector-item selector-item-last";
                } else {
                    echo "form-input selector-item";
                }?>"
                >
                <?php echo $category; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
    const selectorBtn = document.getElementById('open-selector-btn');
    const categories = document.getElementById('categories');

    selectorBtn.addEventListener('click', () => {
    categories.style.display = categories.style.display === 'none' ? 'flex' : 'none';
    selectorBtn.classList.toggle("open", categories.style.display !== "none");
    });
</script>