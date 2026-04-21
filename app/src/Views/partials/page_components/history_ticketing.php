<form class="history-ticketing-container p-4 gap-4" action="/planner/items" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo hf_e(hf_csrf_token()); ?>">
    <div class="fit-content">
        <p class="m-0">For the tour</p>
        <div class="d-flex flex-column gap-2">
            <div class="d-flex flex-row flex-grow-1">
                <label for="dateSelect" class="me-2">Of</label>
                <select name="date" id="dateSelect" value="" class="flex-grow-1">
                    <option></option>
                </select>
            </div>
            
            <div class="d-flex flex-row flex-grow-1">
                <label for="timeSelect" class="me-2">At</label>
                <select name="time" id="timeSelect" disabled class="flex-grow-1">
                    <option></option>
                </select>
            </div>
            
            <div class="d-flex flex-row">
                <label for="languageSelect" class="me-2">In</label>
                <select name="language" id="languageSelect" disabled class="flex-grow-1">
                    <option></option>
                </select>
            </div>

            <div class="d-flex flex-row">
                <label for="tourSelect" class="me-2">Tour nr</label>
                <select name="tour" id="tourSelect" disabled class="flex-grow-1">
                    <option></option>
                </select>
            </div>

            <div class="d-flex flex-row">
                <label for="ticketCount" class="me-2">For</label>
                <input type="number" name="quantity" id="ticketCount" min="1" max="12" value="1">
                <p class="m-0 ms-2">persons</p>
            </div>

            <div class="d-flex flex-row">
                <label for="familyTicket" class="me-2">Family ticket</label>
                <input type="checkbox" name="familyTicket" id="familyTicket">
            </div>
            
        </div>
    </div>
    <div class="d-flex flex-grow-1 justify-content-center">
        <input type="hidden" name="event_id" id="eventId">
        <?php require __DIR__ . '/component_partials/history_ticket.php'?>
    </div>
</form>

<script>
    const schedule = <?php echo json_encode($data['schedule']); ?>;
    const singleTicketPrice = <?php echo $data['single_ticket_price']; ?>;
    const familyTicketPrice = <?php echo $data['family_ticket_price']; ?>;
</script>
<script src="/js/history_ticketing.js"></script>
<script src="/js/planner_async.js?v=<?php echo rawurlencode((string) @filemtime(__DIR__ . '/../../../../public/js/planner_async.js')); ?>" defer></script>