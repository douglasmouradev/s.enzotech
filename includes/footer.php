        </main>
    </div>
</div>

<div class="modal-overlay" id="modal-overlay" hidden>
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <h3 class="modal-title" id="modal-title">Confirmar</h3>
        <p class="modal-body" id="modal-body"></p>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" id="modal-cancel">Cancelar</button>
            <button type="button" class="btn btn-primary" id="modal-confirm">Confirmar</button>
        </div>
    </div>
</div>

<script>
    window.ENZO_BASE_URL = <?= json_encode(baseUrl(), JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php if (!empty($extraScripts)): ?>
    <?= $extraScripts ?>
<?php endif; ?>
<script src="<?= e(baseUrl('assets/js/main.js')) ?>"></script>
</body>
</html>
