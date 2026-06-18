<div class="row half-width between">
    <p><?php echo $item->location(); ?></p>
    <p><?php if ($item->ticketPrice() > 0) {echo '€' . $item->ticketPrice();} else {echo 'Free';} ?></p>
    <p><?php if ($item->ticketAmount() !== null) {echo $item->ticketAmount() . '/' . ($item->ticketAmount()+$item->getSoldTickets());} ?></p>
    <p><?php echo urldecode($item->category()); ?></p>
</div>