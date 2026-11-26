<?php $__env->startSection('content'); ?>
<div class="section-head anim-up">
    <div>
        <h2><?php echo e($def['title']); ?></h2>
        <p>Kelola <?php echo e(strtolower($def['title'])); ?> Puskesmas Akelamo secara terpusat.</p>
    </div>
    <span class="tag"><?php echo e(number_format(count($rows), 0, ',', '.')); ?> Data</span>
</div>

<div class="toolbar anim-up" style="--delay:.1s">
    <input id="search" class="search" type="search" placeholder="⌕  Cari data..." aria-label="Cari data" autocomplete="off">
    <div class="actions">
        <button class="btn secondary" id="export" type="button">↓ Ekspor CSV</button>
        <?php if($canEdit): ?>
            <button class="btn" id="add" type="button">＋ Tambah Data</button>
        <?php endif; ?>
    </div>
</div>

<div class="table-wrap anim-up" style="--delay:.18s"
     id="crud-root"
     data-module="<?php echo e($module); ?>"
     data-title="<?php echo e($def['title']); ?>"
     data-can-edit="<?php echo e($canEdit ? '1' : '0'); ?>"
     data-columns="<?php echo e(json_encode($def['columns'])); ?>"
     data-fields="<?php echo e(json_encode($def['fields'])); ?>"
     data-doctors="<?php echo e(json_encode($doctors)); ?>"
     data-patients="<?php echo e(json_encode($patients)); ?>"
     data-rows="<?php echo e(json_encode($rows)); ?>">
    
    <p class="empty">Memuat data…</p>
</div>

<p class="footer-note">Menampilkan maksimal 500 entri terbaru. Hindari mengekspor data pribadi kecuali benar-benar diperlukan.</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['crumb' => $module, 'pageTitle' => $def['title']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/modules.blade.php ENDPATH**/ ?>