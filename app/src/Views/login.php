<?php require __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="section auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-9 col-lg-6">
                    <div class="account-card auth-card">
                        <h1>Login</h1>
                        <p class="account-intro">Welcome back. Sign in to continue.</p>

                        <?php if (!empty($message)): ?>
                            <p class="account-message success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <p class="account-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <form method="post" action="/login" class="account-form auth-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars((string) ($redirect ?? '/'), ENT_QUOTES, 'UTF-8'); ?>">
                            <div>
                                <label class="account-label" for="login-identifier">Email or Username</label>
                                <input
                                    id="login-identifier"
                                    class="account-input"
                                    type="text"
                                    name="identifier"
                                    value="<?php echo htmlspecialchars((string) ($_POST['identifier'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                >
                            </div>
                            <div>
                                <label class="account-label" for="login-password">Password</label>
                                <input id="login-password" class="account-input" type="password" name="password" required>
                            </div>
                            <button type="submit" class="btn cta-btn w-100">Login</button>
                        </form>

                        <div class="auth-links">
                            <a href="/password/forgot">Forgot password?</a>
                            <a href="/register?redirect=<?php echo urlencode((string) ($redirect ?? '/')); ?>">Need an account? Register</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
