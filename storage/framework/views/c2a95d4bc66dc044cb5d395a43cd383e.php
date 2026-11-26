<?php $__env->startSection('content'); ?>
<div class="section-head anim-up">
    <div>
        <h2>Terminal &amp; Maintenance</h2>
        <p>Konsol artisan untuk migrasi database dan pengelolaan cache — khusus Super Admin.</p>
    </div>
    <span class="tag">⌨ Whitelist Aman</span>
</div>

<div class="terminal-shell anim-up" style="--delay:.1s" id="terminal-root"
     data-commands="<?php echo e(json_encode(collect($commands)->mapWithKeys(fn ($c) => [$c['name'] => $c['danger']])->all())); ?>">
    <div class="term-bar">
        <span class="term-dot" style="background:#ff5f57"></span>
        <span class="term-dot" style="background:#febc2e"></span>
        <span class="term-dot" style="background:#28c840"></span>
        <span class="term-title">akelamo@puskesmas — admin console</span>
    </div>

    <div class="term-output" id="term-output" aria-live="polite"></div>

    <form class="term-input-row" id="term-form" autocomplete="off">
        <span class="term-prefix">php artisan</span>
        <input id="term-input" type="text" placeholder="ketik perintah… (mis. migrate:status)" maxlength="120"
               spellcheck="false" aria-label="Perintah artisan">
        <button class="btn" type="submit" id="term-run">Jalankan ⏎</button>
    </form>

    <div class="term-pass-row" id="term-pass-row">
        <span class="term-warn">⚠</span>
        <label for="term-pass">Konfirmasi kata sandi akun Anda untuk perintah destruktif:</label>
        <div class="pw-wrap">
            <input id="term-pass" type="password" autocomplete="current-password" maxlength="256">
            <button type="button" class="pw-toggle" data-toggle-password="term-pass" aria-label="Intip kata sandi"
                    aria-pressed="false" title="Intip kata sandi">
                <svg class="eye-open" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
        </div>
    </div>

    <div class="term-actions">
        <?php $__currentLoopData = $commands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button type="button"
                    class="term-btn <?php echo e($c['danger'] ? 'danger' : ''); ?>"
                    data-cmd="<?php echo e($c['name']); ?>">
                <b><?php echo e($c['danger'] ? '⚠' : '›'); ?> <?php echo e($c['name']); ?></b>
                <small><?php echo e($c['desc']); ?></small>
            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<div class="panels anim-up" style="--delay:.2s">
    <section class="panel">
        <h3>🛡️ Cara kerja terminal ini</h3>
        <p class="muted">
            Terminal <b>bukan shell sungguhan</b> — semua perintah dieksekusi lewat <code>Artisan::call()</code> di dalam PHP,
            hanya untuk nama perintah &amp; flag pada <b>whitelist</b> di atas. Perintah lain (mis.
            <code>rm -rf</code>, <code>db:wipe</code>, tinker) otomatis ditolak. Setiap eksekusi tercatat di
            <b>audit log</b> dan dibatasi 12 perintah/menit.
        </p>
    </section>
    <section class="panel">
        <h3>💡 Tips produksi</h3>
        <p class="muted">
            Setelah <code>config:cache</code>, nilai <code>env()</code> tidak lagi terbaca — jalankan
            <code>akelamo:init-admin</code> sebelum caching. Jika ingin mematikan terminal sepenuhnya,
            set <code>TERMINAL_ENABLED=false</code> di <code>.env</code>.
        </p>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['crumb' => 'terminal', 'pageTitle' => 'Terminal'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/terminal.blade.php ENDPATH**/ ?>