<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password</title>
</head>
<body>
    <h1>Forgot password</h1>

    <?php if (!empty($message)): ?>
        <p style="color: #1b5e20;"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p style="color: #b00020;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="/password/forgot">
        <label>
            Email
            <input type="email" name="email" required>
        </label>
        <br>
        <button type="submit">Send reset link</button>
    </form>

    <p><a href="/login">Back to login</a></p>
</body>
</html>
