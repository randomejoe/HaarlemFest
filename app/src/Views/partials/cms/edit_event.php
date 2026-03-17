<div class="cms-item-container vertical-center row">
    <div>
        <div class="vertical-center form-input-container">
            <label for="start_time">Start time:</label>
            <input type="datetime-local" id="start_time" name="start_time" class="form-input" required value="<?php echo $item["start_time"]?>"> 
        </div>
        <div class="vertical-center form-input-container">
            <label for="end_time">End time:</label>
            <input type="datetime-local" id="end_time" name="end_time" class="form-input" required value="<?php echo $item["end_time"]?>"> 
        </div>
        <div class="vertical-center form-input-container">
            <label for="language">Language:</label>
            <input type="text" id="language" name="language" class="form-input" value="<?php echo $item["language"]?>"> 
        </div>
    </div>
    <div>
        <!-- TODO: change to selector for location -->
        <div class="vertical-center form-input-container">
            <label for="location">Location:</label>
            <input type="text" id="location" name="location" class="form-input" required value="<?php echo $item["location"]?>"> 
        </div>
        <!-- TODO: change to use selected location ticket count -->
        <div class="vertical-center form-input-container">
            <label for="amount">Ticket amount:</label>
            <input type="number" id="amount" name="ticket_amount" class="form-input" min="0" required value="<?php echo $item["ticket_amount"]?>"> 
        </div>
        <div class="vertical-center form-input-container">
            <label for="price">Ticket price:</label>
            <input type="number" id="price" name="ticket_price" class="form-input" step="0.01" min="0" required value="<?php echo $item["ticket_price"]?>"> 
        </div>
    </div>
</div>
<div class="vertical-center form-input-container description-container">
    <label for="description">Description:</label>
    <input type="text" id="description" name="description" class="form-input half-width" value="<?php echo $item["description"]?>"> 
</div>
<?php 
$items = $categories;
$initialSelection = $item['category'];
$fieldName = 'category';
require __DIR__ . '/selector.php'; ?>