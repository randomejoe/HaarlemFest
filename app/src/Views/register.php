<?php require __DIR__ . '/partials/header.php'; ?>

<script type="module" src="https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js"></script>

<main>
    <section class="section auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-9 col-lg-6">
                    <div class="account-card auth-card">
                        <h1>Create Account</h1>
                        <p class="account-intro">Join Haarlem Festival to manage your bookings and profile.</p>

                        <?php if (!empty($error)): ?>
                            <p class="account-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <form method="post" action="/register" class="account-form auth-form">
                            <div>
                                <label class="account-label" for="register-username">Username</label>
                                <input
                                    id="register-username"
                                    class="account-input"
                                    type="text"
                                    name="username"
                                    value="<?php echo htmlspecialchars((string) ($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                >
                            </div>
                            <div>
                                <label class="account-label" for="register-email">Email</label>
                                <input
                                    id="register-email"
                                    class="account-input"
                                    type="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                >
                            </div>
                            <div>
                                <label class="account-label" for="register-password">Password</label>
                                <input id="register-password" class="account-input" type="password" name="password" required>
                            </div>
                            <div class="auth-captcha-wrap">
                                <altcha-widget challengeurl="/altcha" name="altcha"></altcha-widget>
                            </div>
                            <button type="submit" class="btn cta-btn w-100">Register</button>
                        </form>

                        <div class="auth-links">
                            <a href="/login">Already have an account? Log in</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
