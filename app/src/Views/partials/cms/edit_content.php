<!DOCTYPE html>
<script src="/js/tinymce/tinymce.min.js"></script>
<div class="cms-item-container vertical-center row">
    <div class="row between">
        <?php
            require __DIR__ . '/component_registry.php';
            $fields = $components[$item->getName()]['fields'];

            $data = $item->getData();
            foreach ($fields as $i => $field) 
            {
                ?>
                <div>
                    <?php
                    echo $field['name'];
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
                        <div class="column">
                            <input type="file" accept="image/*" id="<?php echo $field['name'];?>" name="<?php echo $field['name'] ?>" class="form-input">
                            <image id="preview<?php echo $i?>" src="<?php 
                            if (isset($data[$field['name']])) {
                                echo '/images/' . $data[$field['name']];
                            }?>" width="300" height="300">
                        </div>
                        <script>
                            const <?php echo $field['name'];?>Input = document.getElementById('<?php echo $field['name'];?>');
                            const <?php echo $field['name'];?>Preview = document.getElementById('preview<?php echo $i; ?>');

                            <?php echo $field['name'];?>Input.addEventListener('change', () => {
                                const file = <?php echo $field['name'];?>Input.files[0];

                                if (file) {
                                    <?php echo $field['name'];?>Preview.src = URL.createObjectURL(file);
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