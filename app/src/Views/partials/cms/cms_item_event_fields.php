<div class="row half-width between">
    <p><?php echo $item['location']; ?></p>
    <p><?php echo '€' . $item['ticket_price']; ?></p>
    <p><?php echo ($item['ticket_amount']-$item['sold_tickets']) . '/' . $item['ticket_amount']; ?></p>
    <p><?php echo urldecode($item['category']); ?></p>
</div>