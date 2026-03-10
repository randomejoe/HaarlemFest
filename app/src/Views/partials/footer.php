    <footer class="site-footer mt-5">
        <div class="container py-5">
            <div class="row g-4">
                <section class="col-12 col-md-4 d-grid gap-2">
                    <h4>Quick Links</h4>
                    <a href="#hero">Home</a>
                    <a href="#events">Events</a>
                    <a href="#schedule">Schedule</a>
                    <a href="#locations">Locations</a>
                </section>
                <section class="col-12 col-md-4 d-grid gap-2">
                    <h4>Newsletter</h4>
                    <p class="mb-1">Get updates and line-up announcements.</p>
                    <form class="newsletter" action="#" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <label class="sr-only" for="newsletter-email">Email</label>
                        <div class="input-group">
                            <input id="newsletter-email" class="form-control" type="email" name="email" placeholder="Email address">
                            <button type="submit" class="btn btn-warning">Subscribe</button>
                        </div>
                    </form>
                </section>
                <section class="col-12 col-md-4 d-grid gap-2">
                    <h4>Follow Us</h4>
                    <a href="#">Instagram</a>
                    <a href="#">Facebook</a>
                    <a href="#">X</a>
                </section>
            </div>
        </div>
        <div class="container footer-bottom d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
            <p class="footer-logo mb-0">Haarlem Festival</p>
            <p class="mb-0">Copyright 2026 Haarlem Festival. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
