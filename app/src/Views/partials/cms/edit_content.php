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
                            <?php if (isset($data)) {
                                echo $data[$field['name']];
                            } ?>
                        </textarea>
                        <?php    
                    }
                    ?>
                </div>
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
        ?>
    </div>
</div>