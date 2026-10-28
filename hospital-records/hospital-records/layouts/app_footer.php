<?php
/** @var ?string $modal @var ?string $toast */
?>
        </main>
    </div>

    <?php if (!empty($modal)) { include __DIR__ . '/../partials/modal.php'; } ?>

    <?php if (!empty($toast)): ?>
        <div class="toast" role="status">
            <span><?= icon('shield', 17) ?></span>
            <div>
                <strong>Action completed</strong>
                <p><?= e($toast) ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
