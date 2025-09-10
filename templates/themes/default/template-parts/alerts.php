<?php
/**
 * Professional alert messages template part
 */

// Check for various message types
$messages = array();

// Success messages
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
        case 'created':
            $messages[] = array(
                'type' => 'success',
                'icon' => 'bi-check-circle-fill',
                'text' => 'عملیات با موفقیت انجام شد.'
            );
            break;
        case 'updated':
        case 'edited':
            $messages[] = array(
                'type' => 'success',
                'icon' => 'bi-check-circle-fill',
                'text' => 'اطلاعات با موفقیت به‌روزرسانی شد.'
            );
            break;
        case 'deleted':
            $messages[] = array(
                'type' => 'success',
                'icon' => 'bi-check-circle-fill',
                'text' => 'حذف با موفقیت انجام شد.'
            );
            break;
        case 'completed':
            $messages[] = array(
                'type' => 'success',
                'icon' => 'bi-check-circle-fill',
                'text' => 'عملیات با موفقیت تکمیل شد.'
            );
            break;
        case 'started':
            $messages[] = array(
                'type' => 'info',
                'icon' => 'bi-info-circle-fill',
                'text' => 'عملیات با موفقیت شروع شد.'
            );
            break;
        case 'migration_complete':
            $messages[] = array(
                'type' => 'success',
                'icon' => 'bi-check-circle-fill',
                'text' => 'به‌روزرسانی دیتابیس با موفقیت انجام شد.'
            );
            break;
    }
}

// Error messages (from global variables)
if (isset($error_message) && !empty($error_message)) {
    $messages[] = array(
        'type' => 'danger',
        'icon' => 'bi-exclamation-triangle-fill',
        'text' => $error_message
    );
}

if (isset($error) && !empty($error)) {
    $messages[] = array(
        'type' => 'danger',
        'icon' => 'bi-exclamation-triangle-fill',
        'text' => $error
    );
}

// Success messages (from global variables)
if (isset($message) && !empty($message)) {
    $messages[] = array(
        'type' => 'success',
        'icon' => 'bi-check-circle-fill',
        'text' => $message
    );
}

if (isset($success_message) && !empty($success_message)) {
    $messages[] = array(
        'type' => 'success',
        'icon' => 'bi-check-circle-fill',
        'text' => $success_message
    );
}

// Display messages
foreach ($messages as $msg):
?>
<div class="alert alert-<?php echo $msg['type']; ?> alert-dismissible fade show alert-enter" role="alert">
    <div class="d-flex align-items-center">
        <i class="<?php echo $msg['icon']; ?> me-2 fs-5"></i>
        <div class="flex-grow-1">
            <?php echo htmlspecialchars($msg['text']); ?>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بستن"></button>
</div>
<?php endforeach; ?>
