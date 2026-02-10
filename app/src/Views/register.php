<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
    <script type="module" src="https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js"></script>
</head>
<body>
    <h1>Create account</h1>

    <?php if (!empty($error)): ?>
        <p style="color: #b00020;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="/register">
        <label>
            Username
            <input type="text" name="username" required>
        </label>
        <br>
        <label>
            Email
            <input type="email" name="email" required>
        </label>
        <br>
        <label>
            Password
            <input type="password" name="password" required>
        </label>
        <br>
        <altcha-widget challengeurl="/altcha" name="altcha"></altcha-widget>
        <br>
        <button type="submit">Register</button>
    </form>

    <p><a href="/login">Already have an account? Log in</a></p>
</body>
</html>
