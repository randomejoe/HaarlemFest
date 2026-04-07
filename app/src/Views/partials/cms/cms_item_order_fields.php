<div class="row half-width between">
    <!-- <?php 
    echo '<pre>';
    print_r($item); 
    echo '</pre>';
    ?> -->
    <p><?php echo 'Tickets: ' . $item->getTicketCount(); ?></p>
    <p><?php echo 'Price: ' .$item->getTotalPrice(); ?></p>
    <p><?php $names =  $item->getEventNames(); $text = ''; foreach ($names as $name) {
        $text = $text . ', ' . $name;
    }
    echo 'Events: ' . substr($text, 2); ?></p>
</div>