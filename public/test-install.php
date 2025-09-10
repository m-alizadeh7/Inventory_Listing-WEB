<?php
// Simple test to check installation flow
echo "Testing installation flow...\n";

if (!file_exists(__DIR__ . '/config.php')) {
    echo "✓ config.php not found - should redirect to installer\n";
    echo "Redirecting to: admin/setup-config.php\n";
} else {
    echo "✗ config.php exists - installation check failed\n";
}

if (file_exists(__DIR__ . '/admin/setup-config.php')) {
    echo "✓ Installer found at admin/setup-config.php\n";
} else {
    echo "✗ Installer not found\n";
}

if (file_exists(__DIR__ . '/config-sample.php')) {
    echo "✓ config-sample.php found\n";
} else {
    echo "✗ config-sample.php not found\n";
}
