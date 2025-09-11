<?php
require_once 'bootstrap.php';

// Handle emergency notes operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_note'])) {
        $note_title = clean($_POST['note_title']);
        $note_content = clean($_POST['note_content']);
        $priority = clean($_POST['priority']);
        $inventory_id = !empty($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : null;

        $stmt = $conn->prepare("INSERT INTO emergency_notes (note_title, note_content, priority, inventory_id, created_by) VALUES (?, ?, ?, ?, ?)");
        $user_id = $_SESSION['user_id'] ?? 1;
        if ($stmt->bind_param('sssii', $note_title, $note_content, $priority, $inventory_id, $user_id) && $stmt->execute()) {
            set_flash_message('یادداشت اضطراری با موفقیت اضافه شد', 'success');
        } else {
            set_flash_message('خطا در افزودن یادداشت: ' . $conn->error, 'danger');
        }
    }

    if (isset($_POST['edit_note'])) {
        $id = (int)$_POST['note_id'];
        $note_title = clean($_POST['note_title']);
        $note_content = clean($_POST['note_content']);
        $priority = clean($_POST['priority']);
        $inventory_id = !empty($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : null;

        $stmt = $conn->prepare("UPDATE emergency_notes SET note_title = ?, note_content = ?, priority = ?, inventory_id = ? WHERE id = ?");
        if ($stmt->bind_param('sssii', $note_title, $note_content, $priority, $inventory_id, $id) && $stmt->execute()) {
            set_flash_message('یادداشت اضطراری با موفقیت ویرایش شد', 'success');
        } else {
            set_flash_message('خطا در ویرایش یادداشت: ' . $conn->error, 'danger');
        }
    }

    if (isset($_POST['delete_note'])) {
        $id = (int)$_POST['note_id'];

        if ($conn->query("DELETE FROM emergency_notes WHERE id = $id")) {
            set_flash_message('یادداشت اضطراری با موفقیت حذف شد', 'success');
        } else {
            set_flash_message('خطا در حذف یادداشت: ' . $conn->error, 'danger');
        }
    }

    header('Location: emergency_notes.php');
    exit;
}

// Get emergency notes with inventory info
$emergency_notes = [];
$sql = "SELECT n.*, i.item_name, u.username as creator_name
        FROM emergency_notes n
        LEFT JOIN inventory i ON n.inventory_id = i.id
        LEFT JOIN users u ON n.created_by = u.user_id
        ORDER BY n.priority DESC, n.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $emergency_notes[] = $row;
    }
}

// Get inventory items for dropdown
$inventory_items = [];
$inv_result = $conn->query("SELECT id, item_name FROM inventory ORDER BY item_name");
if ($inv_result) {
    while ($row = $inv_result->fetch_assoc()) {
        $inventory_items[] = $row;
    }
}

$page_title = 'یادداشت‌های اضطراری';
$page_description = 'مدیریت یادداشت‌های اضطراری مربوط به موجودی انبار';

get_header();
?>

<div class="container-fluid px-4">
    <?php include ACTIVE_THEME_PATH . '/templates/emergency_notes.php'; ?>
</div>

<?php get_footer(); ?>
