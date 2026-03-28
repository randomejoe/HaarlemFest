<?php
require_once __DIR__ . '/../../helpers.php';

hf_register_component('lineup_section');

$category = trim((string) ($pageContentItem['title'] ?? ''));
$days = [];
$artistDetailsLinks = [
    'Ntjam Rosie' => '/Ntjam%20Rosie',
    'Jonna Frazer' => '/Jonna%20Frazer',
];

if (isset($eventService) && $category !== '' && method_exists($eventService, 'getLineupDataForCategory')) {
    $days = $eventService->getLineupDataForCategory($category);
}
?>
<section class="lineup-component" id="lineup" data-lineup-root>
    <div class="lineup-shell">
        <h2 class="lineup-title">Line-up</h2>

        <?php if ($days === []): ?>
            <div class="lineup-empty-state">
                No line-up items are available for this festival page yet.
            </div>
        <?php else: ?>
            <div class="lineup-tabs" role="tablist" aria-label="Festival days">
                <?php foreach ($days as $index => $day): ?>
                    <button
                        type="button"
                        class="date-tab<?php echo $index === 0 ? ' is-active' : ''; ?>"
                        data-lineup-tab="<?php echo hf_e($day['key']); ?>"
                        role="tab"
                        aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                        <span><?php echo hf_e($day['label_day']); ?></span>
                        <strong><?php echo hf_e($day['label_date']); ?></strong>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ($days as $index => $day): ?>
                <div
                    class="lineup-day-panel<?php echo $index === 0 ? ' is-active' : ''; ?>"
                    data-lineup-panel="<?php echo hf_e($day['key']); ?>"
                    <?php echo $index === 0 ? '' : 'hidden'; ?>>
                    <div class="lineup-layout">
                        <aside class="schedule-panel" aria-label="Line-up schedule">
                            <div class="section-divider section-divider-left">
                                <span>Schedule</span>
                            </div>

                            <?php if (!empty($day['legend'])): ?>
                                <ul class="legend" aria-label="Venue legend">
                                    <?php foreach ($day['legend'] as $legendItem): ?>
                                        <li>
                                            <span class="legend-dot <?php echo hf_e($legendItem['class']); ?>"></span>
                                            <?php echo hf_e($legendItem['label']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <div class="schedule-card">
                                <div class="schedule-head">
                                    <span>Time</span>
                                    <span>Artist</span>
                                </div>

                                <?php foreach ($day['events'] as $event): ?>
                                    <div
                                        id="lineup-schedule-event-<?php echo (int) $event['id']; ?>"
                                        class="schedule-row <?php echo hf_e($event['venue_class']); ?>">
                                        <span><?php echo hf_e($event['time']); ?></span>
                                        <span>
                                            <?php echo hf_e($event['name']); ?>
                                            <small><?php echo hf_e($event['stage']); ?></small>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </aside>

                        <div class="artist-grid">
                            <?php foreach ($day['events'] as $event): ?>
                                <?php
                                $artistImage = trim((string) ($event['artist_img'] ?? ''));
                                $artistImageUrl = hf_image_url($artistImage);
                                $artistDescription = trim((string) ($event['description'] ?? ''));
                                $artistDetailsUrl = $artistDetailsLinks[$event['name']] ?? '#lineup-schedule-event-' . (int) $event['id'];
                                if ($artistDescription === '') {
                                    $artistDescription = 'Line-up details will be announced soon.';
                                }
                                ?>
                                <article class="artist-card<?php echo $artistImageUrl === '' ? ' is-placeholder' : ''; ?>">
                                    <?php if ($artistImageUrl !== ''): ?>
                                        <img src="<?php echo hf_e($artistImageUrl); ?>" alt="<?php echo hf_e($event['name']); ?>">
                                    <?php endif; ?>
                                    <div class="artist-card-copy">
                                        <h3><?php echo hf_e($event['name']); ?></h3>
                                        <p><?php echo hf_e($artistDescription); ?></p>
                                        <a class="artist-link" href="<?php echo hf_e($artistDetailsUrl); ?>">
                                            Details <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<script>
    (() => {
        const roots = document.querySelectorAll('[data-lineup-root]');

        roots.forEach((root) => {
            if (root.dataset.lineupBound === 'true') {
                return;
            }

            root.dataset.lineupBound = 'true';

            const tabs = Array.from(root.querySelectorAll('[data-lineup-tab]'));
            const panels = Array.from(root.querySelectorAll('[data-lineup-panel]'));

            const activateDay = (dayKey) => {
                tabs.forEach((tab) => {
                    const isActive = tab.dataset.lineupTab === dayKey;
                    tab.classList.toggle('is-active', isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                panels.forEach((panel) => {
                    const isActive = panel.dataset.lineupPanel === dayKey;
                    panel.classList.toggle('is-active', isActive);
                    panel.hidden = !isActive;
                });
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => activateDay(tab.dataset.lineupTab));
            });
        });
    })();
</script>