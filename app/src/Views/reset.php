<?php require __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="section auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-9 col-lg-6">
                    <div class="account-card auth-card">
                        <h1>Reset Password</h1>
                        <p class="account-intro">Set a new password for your account.</p>

                        <?php if (!empty($error)): ?>
                            <p class="account-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <form method="post" action="/password/reset/<?php echo htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="account-form auth-form">
                            <div>
                                <label class="account-label" for="reset-password">New Password</label>
                                <input id="reset-password" class="account-input" type="password" name="password" required>
                            </div>
                            <button type="submit" class="btn cta-btn w-100">Update Password</button>
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
