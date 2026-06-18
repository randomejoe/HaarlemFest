<!DOCTYPE html>
<script src="/js/tinymce/tinymce.min.js"></script>
<div class="cms-item-container vertical-center row fit-content">
    <div class="vertical-center form-input-container">
        <label for="description">Description:</label>
        <textarea name="description" id="description">
            <?php echo $item->getDescription(); ?>
        </textarea>
        <script>
            tinymce.init({
            selector: '#description',
            plugins: 'lists link image code',
            toolbar: 'undo redo | bold italic | bullist numlist | link image | code',
            menubar: false,
            license_key: 'gpl',
            });
        </script> 
    </div>
    <div class="vertical-center form-input-container">
        <label for="imageInput">Image:</label>
        <div>
            <input type="file" accept="image/*" id="imageInput" name="image" class="form-input" value="<?php echo $item->getImage(); ?>">
            <image id="preview" src="<?php echo '/images/' . $item->getImage();?>" width="300" height="300">
        </div>
        
    </div>
</div>
<script>
    const input = document.getElementById('imageInput');
    const preview = document.getElementById('preview');

    input.addEventListener('change', () => {
        const file = input.files[0];

        if (file) {
            preview.src = URL.createObjectURL(file);
        }
    });
</script>