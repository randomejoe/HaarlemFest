<?php require __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="section auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-9 col-lg-6">
                    <div class="account-card auth-card">
                        <h1>Forgot Password</h1>
                        <p class="account-intro">Enter your email and we will send a reset link.</p>

                        <?php if (!empty($message)): ?>
                            <p class="account-message success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <p class="account-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <form method="post" action="/password/forgot" class="account-form auth-form">
                            <div>
                                <label class="account-label" for="forgot-email">Email</label>
                                <input
                                    id="forgot-email"
                                    class="account-input"
                                    type="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                >
                            </div>
                            <button type="submit" class="btn cta-btn w-100">Send Reset Link</button>
                        </form>

                        <div class="auth-links">
                            <a href="/login">Back to login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
