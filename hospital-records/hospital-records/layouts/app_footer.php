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
<script>
(function () {
    var btn = document.getElementById('nav-toggle');
    var bd  = document.getElementById('sidebar-backdrop');
    if (!btn) return;
    function setOpen(open) {
        document.body.classList.toggle('nav-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    btn.addEventListener('click', function () { setOpen(!document.body.classList.contains('nav-open')); });
    if (bd) bd.addEventListener('click', function () { setOpen(false); });
    document.querySelectorAll('#app-sidebar .nav-item').forEach(function (a) {
        a.addEventListener('click', function () { setOpen(false); });
    });
    window.addEventListener('resize', function () { if (window.innerWidth > 760) setOpen(false); });
})();
</script>
</body>
</html>
