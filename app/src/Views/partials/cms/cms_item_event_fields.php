<div class="row half-width between">
    <p><?php echo $item->getLocation(); ?></p>
    <p><?php echo '€' . $item->getTicketPrice(); ?></p>
    <p><?php echo ($item->getTicketAmount()-$item->getSoldTickets()) . '/' . $item->getTicketAmount(); ?></p>
    <p><?php echo urldecode($item->getCategory()); ?></p>
</div>