<div class="row half-width between">
    <p><?php echo $item->location(); ?></p>
    <p><?php echo '€' . $item->ticketPrice(); ?></p>
    <p><?php echo ($item->ticketAmount()-$item->getSoldTickets()) . '/' . $item->ticketAmount(); ?></p>
    <p><?php echo urldecode($item->category()); ?></p>
</div>