<div class="history-ticket gap-2 d-flex flex-column">
    <div class="history-ticket-banner">
        <h3>A stroll through history</h3>
        <img src="/images/<?php echo $data['ticket_image']?>">
    </div>
    <div class="d-flex flex-row gap-2">
        <div class="history-ticket-information left flex-grow-1">
            <div class="d-flex flex-row align-items-center"><p><strong>Location:</strong></p><p class="ms-0" id="location">St. Bavo church</p></div>
            <div class="d-flex flex-row align-items-center"><p><strong>Time:</strong></p><p class="ms-0" id="dateTime">Choose a date</p></div>
            <div class="d-flex flex-row align-items-center"><p><strong>Language:</strong></p><p class="ms-0" id="language">Choose a language</p></div>
            <div class="d-flex flex-row align-items-center"><p><strong>Type of ticket:</strong></p><p class="ms-0" id="type">Single ticket</p></div>
        </div>
        <div class="history-ticket-information right">
            <div class="d-flex flex-row align-items-center"><p><strong>Tickets:</strong></p><p class="ms-0" id="tickets">1</p></div>
            <div class="d-flex flex-row align-items-center"><p><strong>Price:</strong></p><p class="ms-0" id="price"><?php echo number_format(array_first(array_first(array_first(array_first($schedule))))->ticketPrice(), 2);?></p></div>
            <div class="d-flex justify-content-center"><button>Purchase</button></div>
        </div>
    </div> 
</div>