<?php
require_once 'config.php';

// مسیر پوشه مایگریشن‌ها
$migrationsDir = __DIR__ . '/migrations';
// دریافت همه فایل‌های SQL
$files = glob($migrationsDir . '/*.sql');

foreach ($files as $file) {
    $name = basename($file);
    // بررسی اعمال شدن مایگریشن
    $stmt = $conn->prepare("SELECT id FROM migrations WHERE migration = ?");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo "Applying migration: $name...\n";
        $sql = file_get_contents($file);
        if ($conn->multi_query($sql)) {
            // اجرای همه نتایج
            do {
                // هیچ کاری لازم نیست
            } while ($conn->more_results() && $conn->next_result());

            // ثبت مایگریشن اجرا شده
            $ins = $conn->prepare("INSERT INTO migrations (migration, applied_at) VALUES (?, NOW())");
            $ins->bind_param('s', $name);
            $ins->execute();
            $ins->close();

            echo "Done.\n";
        } else {
            die("Error applying migration $name: " . $conn->error);
        }
    } else {
        echo "Skipping migration: $name (already applied)\n";
    }
    $stmt->close();
}

$conn->close();
echo "All migrations processed.\n";
