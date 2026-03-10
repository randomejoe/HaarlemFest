<?php require __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="section">
        <div class="container">
            <div class="account-card">
                <h1>My Account</h1>
                <p class="account-intro">Update your personal details below.</p>

                <?php if (!empty($_GET['updated'])): ?>
                    <p class="account-message success">Your account details were saved.</p>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <p class="account-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <form method="post" action="/account" class="account-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="account-label" for="account-email">Email</label>
                            <input
                                id="account-email"
                                class="account-input"
                                type="email"
                                value="<?php echo htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                readonly
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="account-label" for="account-username">Username</label>
                            <input
                                id="account-username"
                                class="account-input"
                                type="text"
                                name="username"
                                value="<?php echo htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                required
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="account-label" for="account-first-name">First name</label>
                            <input
                                id="account-first-name"
                                class="account-input"
                                type="text"
                                name="first_name"
                                value="<?php echo htmlspecialchars((string) ($user['first_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="account-label" for="account-last-name">Last name</label>
                            <input
                                id="account-last-name"
                                class="account-input"
                                type="text"
                                name="last_name"
                                value="<?php echo htmlspecialchars((string) ($user['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        <div class="col-12">
                            <label class="account-label" for="account-address">Address</label>
                            <input
                                id="account-address"
                                class="account-input"
                                type="text"
                                name="address"
                                value="<?php echo htmlspecialchars((string) ($user['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="account-label" for="account-city">City</label>
                            <input
                                id="account-city"
                                class="account-input"
                                type="text"
                                name="city"
                                value="<?php echo htmlspecialchars((string) ($user['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="account-label" for="account-country">Country</label>
                            <input
                                id="account-country"
                                class="account-input"
                                type="text"
                                name="country"
                                value="<?php echo htmlspecialchars((string) ($user['country'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="account-label" for="account-phone-number">Phone number</label>
                            <input
                                id="account-phone-number"
                                class="account-input"
                                type="text"
                                name="phone_number"
                                value="<?php echo htmlspecialchars((string) ($user['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                    </div>

                    <div class="account-actions">
                        <button type="submit" class="btn cta-btn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
