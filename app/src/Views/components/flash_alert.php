<?php
$message = trim((string) ($flash['message'] ?? ''));

if ($message === '') {
    return;
}

$alertClass = match ((string) ($flash['type'] ?? 'info')) {
    'success' => 'alert-success',
    'error' => 'alert-danger',
    default => 'alert-info',
};
?>

<div class="alert <?php echo htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8'); ?> mb-4" role="alert">
    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
</div>
