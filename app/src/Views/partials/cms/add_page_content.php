<div class='horizontal-center btn'>
    <?php require __DIR__ . '/component_registry.php'; ?>
    <button type="button" class="button" id="addComponentBtn"><div class="row vertical-center"><h2 class="no-margin">+</h2><p class="no-margin">Add content</p></div></button>

    <div id="componentModal" class="modal" style="display: none">
        <div class="modal-content">

            <div class="row between vertical-center"><h2>Add component</h2><button class="button delete-btn" type="button" id="closeModalBtn"><p><strong>X</strong></p></button></div>
            <div class="component-list">
                <?php foreach ($components as $key => $component): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <button name="newContent" value="<?php echo $key ?>" class="component-item button max-width"
                                data-component="<?= $key ?>">
                            <?= htmlspecialchars(str_replace('_', ' ', $key)) ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
    
</div>
<script>

const modal = document.getElementById("componentModal");
const openBtn = document.getElementById("addComponentBtn");
const closeBtn = document.getElementById("closeModalBtn");

openBtn.onclick = () => {
    modal.style.display = "flex";
};

closeBtn.onclick = () => {
    modal.style.display = "none";
};

</script>