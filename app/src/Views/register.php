<?php require __DIR__ . '/partials/header.php'; ?>
<?php $old = $old ?? []; ?>

<script type="module" src="https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js"></script>

<main>
    <section class="section auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-9 col-lg-6">
                    <div class="account-card auth-card">
                        <h1>Create Account</h1>
                        <p class="account-intro">Join Haarlem Festival to manage your bookings and profile.</p>

                        <?php if (!empty($errors)): ?>
                            <ul class="account-message error" style="margin:0;padding-left:1.25rem;">
                                <?php foreach ((array) $errors as $err): ?>
                                    <li><?php echo htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <form method="post" action="/register" class="account-form auth-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars((string) ($redirect ?? '/'), ENT_QUOTES, 'UTF-8'); ?>">
                            <div>
                                <label class="account-label" for="register-username">Username</label>
                                <input
                                    id="register-username"
                                    class="account-input"
                                    type="text"
                                    name="username"
                                    value="<?php echo htmlspecialchars((string) ($old['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
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
                                    value="<?php echo htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                >
                            </div>
                            <div>
                                <label class="account-label" for="register-password">Password</label>
                                <input id="register-password" class="account-input" type="password" name="password" minlength="12" required>
                                <small>Use at least 12 characters with uppercase, lowercase, number, and special character.</small>
                            </div>
                            <div class="auth-captcha-wrap">
                                <altcha-widget challengeurl="/altcha" name="altcha"></altcha-widget>
                            </div>
                            <button type="submit" class="btn cta-btn w-100">Register</button>
                        </form>

                        <div class="auth-links">
                            <a href="/login?redirect=<?php echo urlencode((string) ($redirect ?? '/')); ?>">Already have an account? Log in</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
