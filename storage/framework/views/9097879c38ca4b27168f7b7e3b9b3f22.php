<?php $__env->startSection('content'); ?>
<div class="section-head anim-up">
    <div>
        <h2>Notifikasi Sistem</h2>
        <p><?php echo e($unread > 0 ? $unread.' belum dibaca' : 'Semua sudah dibaca'); ?> — peristiwa transaksi, registrasi pasien, dan resep.</p>
    </div>
    <button class="btn secondary" type="button" id="mark-read-btn" <?php echo e($unread === 0 ? 'disabled' : ''); ?>>✓ Tandai Semua Dibaca</button>
</div>

<div class="panel anim-up" style="--delay:.08s">
    <?php $__empty_1 = true; $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <a class="notif-row <?php echo e($n->read_at ? '' : 'unread'); ?>" href="<?php echo e($n->url ?: '#'); ?>">
            <span class="notif-icon"><?php echo e(\App\Models\AppNotification::iconFor($n->type)); ?></span>
            <span>
                <b><?php echo e($n->title); ?></b>
                <?php if($n->body): ?><small><?php echo e($n->body); ?></small><?php endif; ?>
            </span>
            <time><?php echo e($n->created_at->locale('id')->diffForHumans()); ?></time>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <p class="empty">Belum ada notifikasi. Transaksi keuangan, registrasi pasien baru, dan resep akan muncul di sini.</p>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['crumb' => 'notifikasi', 'pageTitle' => 'Notifikasi'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/notifikasi.blade.php ENDPATH**/ ?>