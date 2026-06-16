<div class="cms-item-container vertical-center row fit-content">
    <div>
        <div class="vertical-center form-input-container">
            <label for="start_time">Start time:</label>
            <input type="datetime-local" id="start_time" name="start_time" class="form-input" required value="<?php echo $item->startsAt()->format('Y-m-d\TH:i'); ?>">
        </div>
        <div class="vertical-center form-input-container">
            <label for="end_time">End time:</label>
            <input type="datetime-local" id="end_time" name="end_time" class="form-input" required value="<?php echo $item->endsAt()->format('Y-m-d\TH:i'); ?>">
        </div>
        <div class="vertical-center form-input-container">
            <label for="language">Language:</label>
            <input type="text" id="language" name="language" class="form-input" value="<?php echo $item->getLanguage()->value ?? ''; ?>">
        </div>
    </div>
    <div>
        <!-- TODO: change to selector for location -->
        <div class="vertical-center form-input-container">
            <label for="location">Location:</label>
            <input type="text" id="location" name="location" class="form-input" required value="<?php echo $item->location(); ?>">
        </div>
        <!-- TODO: change to use selected location ticket count -->
        <div class="vertical-center form-input-container">
            <label for="amount">Ticket amount:</label>
            <input type="number" id="amount" name="ticket_amount" class="form-input" min="0" required value="<?php echo $item->ticketAmount(); ?>">
        </div>
        <div class="vertical-center form-input-container">
            <label for="price">Ticket price:</label>
            <input type="number" id="price" name="ticket_price" class="form-input" step="0.01" min="0" required value="<?php echo $item->ticketPrice(); ?>">
        </div>
    </div>
</div>
<div class="vertical-center form-input-container description-container fit-content">
    <label for="description">Description:</label>
    <input type="text" id="description" name="description" class="form-input half-width" value="<?php echo $item->description(); ?>">
</div>
<div id="artistImgContainer" class="vertical-center form-input-container fit-content" style="display:none;">
    <label for="artistImgInput">Artist Image:</label>
    <div>
        <input type="hidden" name="artist_img" id="artistImgHidden" value="<?php echo $item->artistImg() ?? ''; ?>">
        <input type="file" accept="image/*" id="artistImgInput" name="artist_img" class="form-input">
        <?php if ($item->artistImg()): ?>
            <image id="artistPreview" src="<?php echo '/images/' . $item->artistImg(); ?>" width="200" height="200">
            <?php else: ?>
                <image id="artistPreview" src="" width="200" height="200" style="display:none;">
                <?php endif; ?>
    </div>
</div>
<?php
$items = $categories;
$initialSelection = $item->category();
$fieldName = 'category';
// TODO: turn into selector
require __DIR__ . '/event_selector.php'; ?>
<script>
    // Wait for DOM to be ready and category selector to exist
    function initArtistImageField() {
        const categorySelector = document.querySelector('select[name="category"]');
        const artistImgContainer = document.getElementById('artistImgContainer');
        const artistImgInput = document.getElementById('artistImgInput');
        const artistPreview = document.getElementById('artistPreview');
        const artistImgHidden = document.getElementById('artistImgHidden');

        if (!categorySelector || !artistImgContainer) {
            // Retry after a short delay if elements not found
            setTimeout(initArtistImageField, 100);
            return;
        }

        function updateArtistImageVisibility() {
            const selectedCategory = (categorySelector.value || '').toLowerCase().trim();
            if (selectedCategory.includes('jazz')) {
                artistImgContainer.style.display = 'block';
            } else {
                artistImgContainer.style.display = 'none';
                artistImgInput.value = '';
            }
        }

        // Set initial visibility
        updateArtistImageVisibility();

        // Listen for category changes
        categorySelector.addEventListener('change', updateArtistImageVisibility);

        // Handle file input changes
        artistImgInput.addEventListener('change', () => {
            const file = artistImgInput.files[0];
            if (file) {
                artistPreview.src = URL.createObjectURL(file);
                artistPreview.style.display = 'block';
                artistImgHidden.value = '';
            }
        });
    }

    // Initialize when document is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initArtistImageField);
    } else {
        initArtistImageField();
    }
</script>