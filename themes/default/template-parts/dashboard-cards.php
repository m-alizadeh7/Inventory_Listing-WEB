<?php
/**
 * Dashboard cards template part
 * Used in home.php template
 */

// Define dashboard cards
$dashboard_cards = array(
    array(
        'title' => 'انبارگردانی',
        'icon' => 'bi-boxes',
        'text' => 'انجام انبارگردانی و مدیریت موجودی.',
        'link' => 'inventory_records.php',
        'icon_bg' => 'bg-primary bg-opacity-10',
        'icon_color' => 'text-primary',
        'button_class' => 'btn-primary'
    ),
    array(
        'title' => 'سفارشات تولید',
        'icon' => 'bi-list-check',
        'text' => 'مدیریت سفارشات تولید و مراحل آن.',
        'link' => 'production_orders.php',
        'icon_bg' => 'bg-success bg-opacity-10',
        'icon_color' => 'text-success',
        'button_class' => 'btn-success'
    ),
    array(
        'title' => 'دستگاه‌ها و BOM',
        'icon' => 'bi-hdd-stack',
        'text' => 'مدیریت لیست دستگاه‌ها و قطعات آن‌ها.',
        'link' => 'devices.php',
        'icon_bg' => 'bg-secondary bg-opacity-10',
        'icon_color' => 'text-secondary',
        'button_class' => 'btn-secondary'
    )
);
?>

<div class="row g-4">
    <?php foreach ($dashboard_cards as $card): ?>
        <?php the_dashboard_card($card); ?>
    <?php endforeach; ?>
</div>
