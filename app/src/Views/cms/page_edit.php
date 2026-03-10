<div>
    <?php include __DIR__ . '/../components/cms_nav.php';?>
    <div class='horizontal-center vertical-center'>
        <div class='cms-container'>
            <?php 
            foreach ($pages as $page) {
                $title = $page['title'];
                require __DIR__ . '/../components/page_item.php';
            } ?>
            </div>
        </div>
    </div>
</div>