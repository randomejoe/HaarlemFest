<div>
<script src="/js/tinymce/tinymce.min.js"></script>

<textarea name="content" id="content"  ><?php echo $item['content']; ?></textarea>

<script>
tinymce.init({
  selector: '#content',
  plugins: 'lists link image code',
  toolbar: 'undo redo | bold italic | bullist numlist | link image | code',
  menubar: false,
  license_key: 'gpl',
  forced_root_block: 'div',
});
</script></div>