<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>

    <?php if (!empty($message)): ?>
        <p style="color: #1b5e20;"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p style="color: #b00020;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="/login">
        <label>
            Email or Username
            <input type="text" name="identifier" required>
        </label>
        <br>
        <label>
            Password
            <input type="password" name="password" required>
        </label>
        <br>
        <button type="submit">Login</button>
    </form>

    <p><a href="/password/forgot">Forgot password?</a></p>
    <p><a href="/register">Need an account? Register</a></p>
</body>
</html>
