<?php
/**
 * Professional page header template part
 * 
 * @param array $args Header arguments
 */

$defaults = array(
    'title' => '',
    'subtitle' => '',
    'icon' => '',
    'breadcrumbs' => array(),
    'actions' => array()
);

$header = array_merge($defaults, $args ?? array());
?>

<div class="page-header fade-in">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div class="flex-grow-1">
            <?php if ($header['icon']): ?>
                <div class="page-icon mb-3">
                    <i class="<?php echo $header['icon']; ?> fs-1 gradient-text"></i>
                </div>
            <?php endif; ?>
            
            <h1 class="mb-2">
                <?php echo htmlspecialchars($header['title']); ?>
            </h1>
            
            <?php if ($header['subtitle']): ?>
                <p class="text-muted fs-5 mb-0">
                    <?php echo htmlspecialchars($header['subtitle']); ?>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($header['breadcrumbs'])): ?>
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb">
                        <?php foreach ($header['breadcrumbs'] as $breadcrumb): ?>
                            <?php if (isset($breadcrumb['url'])): ?>
                                <li class="breadcrumb-item">
                                    <a href="<?php echo $breadcrumb['url']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($breadcrumb['text']); ?>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="breadcrumb-item active" aria-current="page">
                                    <?php echo htmlspecialchars($breadcrumb['text']); ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($header['actions'])): ?>
            <div class="page-actions fade-in-delay-1">
                <?php foreach ($header['actions'] as $action): ?>
                    <a href="<?php echo $action['url']; ?>" 
                       class="btn <?php echo $action['class'] ?? 'btn-primary'; ?> btn-icon me-2">
                        <?php if (isset($action['icon'])): ?>
                            <i class="<?php echo $action['icon']; ?>"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($action['text']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
