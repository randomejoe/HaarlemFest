<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password</title>
</head>
<body>
    <h1>Reset password</h1>

    <?php if (!empty($error)): ?>
        <p style="color: #b00020;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post" action="/password/reset/<?php echo htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <label>
            New password
            <input type="password" name="password" required>
        </label>
        <br>
        <button type="submit">Update password</button>
    </form>

    <p><a href="/login">Back to login</a></p>
</body>
</html>
