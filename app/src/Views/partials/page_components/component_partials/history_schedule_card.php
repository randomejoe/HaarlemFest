<div class="horizontal-center"> 
    <div class="history-schedule-card"><?php 
        ?><div class="horizontal-center history-schedule-card-time">
            <p class="no-margin"><?php echo $time; ?></p>
        </div>
        <div class="history-schedule-card-content">
            <div class="history-schedule-card-rows"><?php
            foreach ($scheduleTime as $language => $languageEvents) {
                $tourCount = count($languageEvents);
                ?>
                    <div class="history-schedule-card-row">
                        <p class="no-margin"><?php 
                            $tourText =  $tourCount . ' ' . $language . ' tour';
                            if ($tourCount > 1) {$tourText .= 's';}
                            $tourText .= '.';
                            echo $tourText;
                        ?></p>
                        <img src="/images/<?php echo ucfirst($language) ?>_flag.png" class="tour-language-image"></img>
                    </div>
                <?php
            }
            ?>
            </div>
            <div class="d-flex justify-content-center"><a class="history-add-ticket-button text-center" href="<?php echo $data['buy_ticket_button_link'];?> ">Get tickets</a></div>
            
        </div>
    </div>
</div>