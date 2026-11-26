<?php $__env->startSection('content'); ?>
<div class="section-head anim-up">
    <div>
        <h2>Log Aktivitas Aplikasi</h2>
        <p>Semua aksi penting tercatat: login, CRUD data, ekspor, ganti sandi, terminal, penyerahan resep, dll.</p>
    </div>
    <span class="tag">☰ Audit Trail</span>
</div>

<section class="panel anim-up" style="--delay:.06s">
    <form method="GET" action="<?php echo e(url('/log-aktivitas')); ?>" class="chart-head">
        <div class="field" style="max-width:340px;margin:0">
            <input type="text" name="q" value="<?php echo e($search); ?>" placeholder="Cari aksi / entitas / username…" maxlength="60">
        </div>
        <button class="btn secondary" type="submit">🔎 Cari</button>
        <a class="btn secondary" href="<?php echo e(url('/log-aktivitas')); ?>">Reset</a>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Entitas</th><th>Referensi</th></tr></thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($log->created_at->locale('id')->translatedFormat('d M Y, H:i')); ?></td>
                    <td><b><?php echo e($log->actor?->full_name ?? 'Publik/Anonim'); ?></b> <small class="muted"><?php echo e($log->actor?->role); ?></small></td>
                    <td><span class="tag <?php echo e(str_starts_with($log->action, 'DELETE') ? 'tag-danger' : ''); ?>"><?php echo e($log->action); ?></span></td>
                    <td><?php echo e($log->entity); ?></td>
                    <td><?php echo e($log->record_id !== null ? '#'.$log->record_id : '–'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td class="empty" colspan="5">Tidak ada aktivitas<?php echo e($search ? ' untuk pencarian "'.$search.'"' : ''); ?>.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php echo e($rows->links()); ?>

</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['crumb' => 'log', 'pageTitle' => 'Log Aktivitas'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/log.blade.php ENDPATH**/ ?>