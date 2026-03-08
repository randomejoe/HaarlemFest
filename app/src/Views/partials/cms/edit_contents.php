<div class="cms-item between row vertical-center">
    <div class="row vertical-center">
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
                        <script src="/js/tinymce/tinymce.min.js"></script>
                        <textarea name="content" id="content">
                            <?php echo '<' . $field['element'] . '>' . $data[$field['name']] . '</' . $field['element'] . '>'; ?>
                        </textarea>
                        <?php    
                    }
                    ?>
                </div>
                <?php
            }
        ?>
        <script>
            tinymce.init({
            selector: '#content',
            plugins: 'lists link image code',
            toolbar: 'undo redo | bold italic | bullist numlist | link image | code',
            menubar: false,
            license_key: 'gpl',
            height: 150,
            });
        </script> 
    </div>
</div>