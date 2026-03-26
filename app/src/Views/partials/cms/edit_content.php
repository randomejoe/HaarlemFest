<!DOCTYPE html>
<script src="/js/tinymce/tinymce.min.js"></script>
<div class="cms-item-container cms-content-editor column">
    <div class="cms-content-editor__fields column">
        <?php
        require __DIR__ . '/component_registry.php';
        $fields = $components[$item->getName()]['fields'];

        $data = $item->getData();
        if ($fields === []) {
        ?>
            <p>This component has no editable fields. It renders automatically from the page context.</p>
        <?php
        }

        foreach ($fields as $i => $field) {
        ?>
            <div class="cms-content-editor__field">
                <?php
                ?><label for="<?php echo $field['type'] === 'text' ? 'textField' . $i : $field['name']; ?>"><?php echo $field['name']; ?></label><?php
                                                                                                                                                        if ($field['type'] == 'text') {
                                                                                                                                                        ?>
                    <textarea name="<?php echo $field['name'] ?>" id="textField<?php echo $i; ?>">
                            <?php if (isset($data[$field['name']])) {
                                                                                                                                                                echo $data[$field['name']];
                                                                                                                                                            } ?>
                        </textarea>
                    <script>
                        tinymce.init({
                            selector: '#textField<?php echo $i; ?>',
                            plugins: 'lists link image code',
                            toolbar: 'undo redo | bold italic | bullist numlist | link image | code',
                            menubar: false,
                            license_key: 'gpl',
                            height: 150,
                            forced_root_block: "<?php echo $field['element']; ?>"
                        });
                    </script>
                <?php
                                                                                                                                                        }
                                                                                                                                                        if ($field['type'] == 'image') {
                ?>
                    <div class="column cms-content-editor__image-field">                        <input type="hidden" name="<?php echo $field['name']; ?>_existing" value="<?php echo isset($data[$field['name']]) ? htmlspecialchars($data[$field['name']], ENT_QUOTES, 'UTF-8') : ''; ?>">                        <input type="file" accept="image/*" id="<?php echo $field['name']; ?>" name="<?php echo $field['name'] ?>" class="form-input">
                        <image id="preview<?php echo $i ?>" src="<?php
                                                                                                                                                            if (isset($data[$field['name']])) {
                                                                                                                                                                echo '/images/' . $data[$field['name']];
                                                                                                                                                            } ?>" width="300" height="300">
                    </div>
                    <script>
                        const <?php echo $field['name']; ?>Input = document.getElementById('<?php echo $field['name']; ?>');
                        const <?php echo $field['name']; ?>Preview = document.getElementById('preview<?php echo $i; ?>');

                        <?php echo $field['name']; ?>Input.addEventListener('change', () => {
                            const file = <?php echo $field['name']; ?>Input.files[0];

                            if (file) {
                                <?php echo $field['name']; ?>Preview.src = URL.createObjectURL(file);
                            }
                        });
                    </script>
                <?php
                                                                                                                                                        }
                ?>
            </div>
        <?php
        }
        ?>
    </div>
</div>