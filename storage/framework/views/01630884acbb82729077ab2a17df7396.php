<?php $__env->startSection('content'); ?>
<div class="section-head anim-up">
    <div>
        <h2>Survei Kepuasan Masyarakat</h2>
        <p>Bagikan QR Code agar pengunjung dapat menilai layanan secara anonim.</p>
    </div>
    <span class="tag">Publik &amp; Tanpa Login</span>
</div>

<div class="stats stats-two anim-up" style="--delay:.1s">
    <div class="stat stat-animated">
        <div class="icon-tile">☆</div>
        <small>Total Responden</small>
        <strong class="count-up" data-count="<?php echo e($stats['total']); ?>" data-format="number">0</strong>
        <em>Survei diterima</em>
    </div>
    <div class="stat stat-animated" style="--delay:.12s">
        <div class="icon-tile">★</div>
        <small>Rata-rata Kepuasan</small>
        <strong class="count-up" data-count="<?php echo e(round((float) $stats['average'], 2)); ?>" data-format="rating">0</strong>
        <em>Skala penilaian 1 sampai 5</em>
    </div>
</div>

<section class="panel anim-up" style="--delay:.2s">
    <h3>📱 QR Code Survei Online</h3>
    <div class="qr-panel">
        <img src="<?php echo e(route('surveys.qr')); ?>" alt="QR Code untuk mengisi survei kepuasan Puskesmas Akelamo">
        <div class="qr-side">
            <p class="muted">Pasang QR ini di ruang tunggu atau loket. Masyarakat dapat memindainya menggunakan kamera ponsel tanpa perlu akun.</p>
            <div class="qr-link"><?php echo e($link); ?></div>
            <button class="btn secondary" id="copy-link" type="button" data-link="<?php echo e($link); ?>">Salin Tautan</button>
            <a class="btn" href="<?php echo e(route('surveys.qr')); ?>" target="_blank" rel="noopener">Buka QR</a>
        </div>
    </div>
</section>

<div class="section-head anim-up" style="--delay:.28s"><h2>Umpan Balik Terbaru</h2></div>

<div class="table-wrap anim-up" style="--delay:.34s" id="surveys-table"
     data-rows="<?php echo e(json_encode($items->map(fn ($s) => [
         'id' => $s->id,
         'created_at' => $s->created_at?->locale('id')->translatedFormat('d M Y H:i'),
         'service' => $s->service,
         'rating' => $s->rating,
         'suggestion' => $s->suggestion,
     ]))); ?>">
    <table class="data-table">
        <thead>
        <tr>
            <th>Tanggal</th><th>Jenis Layanan</th><th>Rating</th><th>Kritik &amp; Saran</th><th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($s->created_at?->locale('id')->translatedFormat('d M Y H:i')); ?></td>
                <td><?php echo e($s->service); ?></td>
                <td><span class="pill"><?php echo e($s->rating); ?> / 5 ★</span></td>
                <td><?php echo e($s->suggestion ?: '–'); ?></td>
                <td><button class="btn tiny danger" data-survey-delete="<?php echo e($s->id); ?>" type="button">Hapus</button></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="5" class="empty">Belum ada penilaian.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['crumb' => 'surveys', 'pageTitle' => 'Survei Kepuasan'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/surveys.blade.php ENDPATH**/ ?>