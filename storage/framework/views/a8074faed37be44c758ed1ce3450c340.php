<?php $__env->startSection('content'); ?>
<div class="section-head anim-up">
    <div>
        <h2>Laporan &amp; Ekspor Data</h2>
        <p>Pilih jenis laporan dan rentang tanggal, lalu unduh dalam format Excel atau PDF.</p>
    </div>
    <span class="tag">📊 Filter Tanggal · Excel &amp; PDF</span>
</div>

<form class="report-filters anim-up" style="--delay:.08s" id="report-form">
    <div class="field">
        <label for="report_type">Jenis Laporan</label>
        <select id="report_type" name="report_type" required>
            <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($key); ?>" <?php if($loop->first): echo 'selected'; endif; ?>><?php echo e($t['title']); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
    <div class="field">
        <label for="start">Tanggal Mulai</label>
        <input type="date" id="start" name="start" value="<?php echo e($start); ?>" max="<?php echo e(now()->toDateString()); ?>" required>
    </div>
    <div class="field">
        <label for="end">Tanggal Akhir</label>
        <input type="date" id="end" name="end" value="<?php echo e($end); ?>" max="<?php echo e(now()->toDateString()); ?>" required>
    </div>
    <div class="report-actions">
        <button class="btn" type="submit">🔎 Tampilkan</button>
        <a class="btn secondary btn-export" data-href="<?php echo e(url('/laporan/export/excel')); ?>" href="#">↓ Excel (.xlsx)</a>
        <a class="btn secondary btn-export" data-href="<?php echo e(url('/laporan/export/pdf')); ?>" href="#">🧾 PDF</a>
    </div>
</form>

<div id="report-output" class="anim-up" style="--delay:.15s">
    <p class="empty">Memuat laporan…</p>
</div>

<p class="footer-note">Setiap ekspor tercatat dalam log audit (jenis, format, dan pengunduh). Ekspor hanya data pada rentang tanggal terpilih.</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['crumb' => 'laporan', 'pageTitle' => 'Laporan'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/laporan.blade.php ENDPATH**/ ?>