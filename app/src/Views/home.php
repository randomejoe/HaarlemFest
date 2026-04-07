<?php require __DIR__ . '/partials/header.php'; ?>

<main>
    <section id="hero" class="hero section d-flex align-items-center">
        <div class="hero-bg"></div>
        <div class="container hero-content position-relative text-white">
            <h1 class="display-2 fw-bold">The Haarlem Festival</h1>
            <p class="lead">
                A week where music, dance, food and culture meet in one city.
                Explore every side of Haarlem from July 26 to August 2.
            </p>
            <a href="#events" class="btn cta-btn">Explore Events</a>
        </div>
    </section>

    <section id="events" class="events section">
        <div class="container">
            <h2>Our Events</h2>
            <p class="section-intro">
                Four themed experiences, each with its own atmosphere. Choose your route
                through the festival and mix live music, nightlife, food and history.
            </p>
            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <article class="event-card">
                        <img src="/images/jazz_thumb.jpg" alt="Live jazz performance">
                        <div class="event-overlay"></div>
                        <div class="event-copy">
                            <span>MUSIC EXPERIENCE</span>
                            <h3>Haarlem Jazz</h3>
                        </div>
                        <a class="stretched-link" href="/jazz" aria-label="Open Jazz program"></a>
                    </article>
                </div>
                <div class="col-12 col-md-6">
                    <article class="event-card">
                        <img src="/images/dance_thumb.jpg" alt="Crowd dancing at night">
                        <div class="event-overlay"></div>
                        <div class="event-copy">
                            <span>ELECTRONIC BEATS</span>
                            <h3>Dance!</h3>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-md-6">
                    <article class="event-card">
                        <img src="/images/yummy_thumb.jpg" alt="Street food dishes">
                        <div class="event-overlay"></div>
                        <div class="event-copy">
                            <span>CULINARY JOURNEY</span>
                            <h3>Yummy!</h3>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-md-6">
                    <article class="event-card">
                        <img src="/images/haarlem_thumb.jpg" alt="Historic Haarlem architecture">
                        <div class="event-overlay"></div>
                        <div class="event-copy">
                            <span>CULTURAL HERITAGE</span>
                            <h3>History</h3>
                        </div>
                        <a class="stretched-link" href="/A stroll through history" aria-label="Open History program"></a>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section id="schedule" class="schedule section">
        <div class="container">
            <h2>The Schedule</h2>
            <p class="section-intro">
                Plan your full festival week. Every day runs from early afternoon into late night,
                with curated moments for each event theme.
            </p>
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="day-card h-100">
                        <header>
                            <h3>Thursday</h3>
                            <p>July 26</p>
                        </header>
                        <ul class="mb-0">
                            <li><span>13:00</span> Opening Plaza Show</li>
                            <li><span>15:30</span> Jazz Sessions Start</li>
                            <li><span>18:00</span> Street Food Route</li>
                            <li><span>21:00</span> Night Dance Program</li>
                        </ul>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="day-card h-100">
                        <header>
                            <h3>Friday</h3>
                            <p>July 27</p>
                        </header>
                        <ul class="mb-0">
                            <li><span>12:00</span> Cultural Walking Tour</li>
                            <li><span>14:00</span> Local Artist Stages</li>
                            <li><span>17:00</span> Food Labs</li>
                            <li><span>22:00</span> Electronic Headliners</li>
                        </ul>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="day-card h-100">
                        <header>
                            <h3>Saturday</h3>
                            <p>July 28</p>
                        </header>
                        <ul class="mb-0">
                            <li><span>11:00</span> Family Program</li>
                            <li><span>15:00</span> Jazz Main Stage</li>
                            <li><span>19:00</span> Culinary Night Market</li>
                            <li><span>23:00</span> Dance Marathon</li>
                        </ul>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="day-card h-100">
                        <header>
                            <h3>Sunday</h3>
                            <p>August 2</p>
                        </header>
                        <ul class="mb-0">
                            <li><span>12:30</span> History Reenactment</li>
                            <li><span>16:00</span> Cross-over Concert</li>
                            <li><span>19:30</span> Closing Feast</li>
                            <li><span>22:30</span> Finale Lights</li>
                        </ul>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section id="locations" class="locations section">
        <div class="container">
            <h2>Locations</h2>
            <p class="section-intro">
                The festival is spread across Haarlem city center. Start from one of our easy
                arrival points and follow the route markers between venues.
            </p>
            <div class="row g-3 align-items-stretch">
                <div class="col-12 col-lg-8">
                    <figure class="map-wrap h-100 mb-0">
                        <img src="/images/Map.jpg" alt="Festival locations map">
                    </figure>
                </div>
                <div class="col-12 col-lg-4">
                    <aside class="start-panel h-100">
                        <h3>Starting Locations</h3>
                        <ul>
                            <li><strong>Coming by train</strong><span>Haarlem Station</span></li>
                            <li><strong>Coming by car</strong><span>Raaks Parking</span></li>
                            <li><strong>Coming by bike</strong><span>Grote Markt Racks</span></li>
                            <li><strong>Start with Jazz</strong><span>Open Air Main Stage</span></li>
                            <li><strong>Start with Food</strong><span>Culinary Square</span></li>
                        </ul>
                    </aside>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
