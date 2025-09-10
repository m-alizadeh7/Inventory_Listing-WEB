<?php
/**
 * Professional stats cards template part
 * 
 * @param array $stats Array of stats to display
 */

if (empty($stats) || !is_array($stats)) {
    return;
}
?>

<div class="row g-4 mb-4">
    <?php foreach ($stats as $index => $stat): ?>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card card-enter hover-lift" style="animation-delay: <?php echo $index * 0.1; ?>s">
                <div class="card-body text-center">
                    <?php if (isset($stat['icon'])): ?>
                        <div class="stats-icon mb-3">
                            <i class="<?php echo $stat['icon']; ?> fs-1 <?php echo $stat['icon_color'] ?? 'text-primary'; ?>"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="display-5 fw-bold <?php echo $stat['value_color'] ?? 'text-primary'; ?> mb-2">
                        <?php echo $stat['value']; ?>
                    </div>
                    
                    <h6 class="text-muted mb-0">
                        <?php echo htmlspecialchars($stat['label']); ?>
                    </h6>
                    
                    <?php if (isset($stat['change'])): ?>
                        <small class="<?php echo $stat['change'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <i class="bi bi-<?php echo $stat['change'] >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                            <?php echo abs($stat['change']); ?>%
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
