<!DOCTYPE html>
<div class="cms-item-container vertical-center row">
    <div class="row between vertical-center">
        <?php
            require __DIR__ . '/component_registry.php';
            $fields = $components[$item['item_name']]['fields'];

            if (isset($item['data'])) 
            {
                $data = json_decode($item['data'], true);
            }
            foreach ($fields as $field) 
            {
                ?>
                <div>
                    <?php
                    echo $field['name'];
                    if ($field['type'] == 'text') {
                        ?>
                        <textarea name="<?php echo $field['name'] ?>" id="textField">
                            <?php if (isset($data)) {
                                echo '<' . $field['element'] . '>' . $data[$field['name']] . '</' . $field['element'] . '>';
                            } else {
                                echo '<' . $field['element'] . '></' . $field['element'] . '>';
                            } ?>
                        </textarea>
                        <?php    
                    }
                    ?>
                </div>
                <?php
            }
        ?>
        <script src="/js/tinymce/tinymce.min.js"></script>
        <script>
            tinymce.init({
            selector: '#textField',
            plugins: 'lists link image code',
            toolbar: 'undo redo | bold italic | bullist numlist | link image | code',
            menubar: false,
            license_key: 'gpl',
            height: 150,
            });
        </script> 
    </div>
</div>