<?php $__env->startSection('content'); ?>
<div class="section-head anim-up">
    <div>
        <h2>Resep &amp; Obat</h2>
        <p><?php if($canWrite): ?>Tulis resep untuk pasien — farmasi menyerahkan obat &amp; stok berkurang otomatis.<?php else: ?> Pilih pasien, lihat resep dari dokter, lalu tandai penyerahan obat.<?php endif; ?></p>
    </div>
    <span class="tag">℞ <?php echo e($list->where('status','aktif')->count()); ?> resep menunggu</span>
</div>

<?php if($canWrite): ?>
<section class="panel anim-up">
    <h3>✎ Tulis Resep Baru</h3>
    <form id="rx-form" data-endpoint="<?php echo e(url('/resep')); ?>">
        <?php echo csrf_field(); ?>
        <div class="rx-grid">
            <div class="field">
                <label for="rx-patient">Pasien</label>
                <select id="rx-patient" name="patient_id" required>
                    <option value="">— pilih pasien —</option>
                    <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p->id); ?>"><?php echo e($p->medical_record_no); ?> · <?php echo e($p->full_name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="field">
                <label for="rx-notes">Catatan Klinis (opsional, terenkripsi)</label>
                <input type="text" id="rx-notes" name="notes" maxlength="500" placeholder="mis. tekanan darah 140/90, kontrol 2 minggu">
            </div>
        </div>

        <div class="rx-items" id="rx-items">
            <div class="rx-item">
                <div class="field">
                    <label>Obat</label>
                    <select name="items[0][medicine_id]" required>
                        <option value="">— pilih obat —</option>
                        <?php $__currentLoopData = $medicines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($m->id); ?>" data-stock="<?php echo e($m->stock); ?>"><?php echo e($m->code); ?> · <?php echo e($m->name); ?> (stok <?php echo e($m->stock); ?> <?php echo e($m->unit); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="field">
                    <label>Aturan Pakai</label>
                    <input type="text" name="items[0][dose]" maxlength="140" placeholder="3x1 sesudah makan" required>
                </div>
                <div class="field rx-qty">
                    <label>Jml</label>
                    <input type="number" name="items[0][quantity]" min="1" max="1000" value="1" required>
                </div>
                <button type="button" class="rx-del" title="Hapus baris">✕</button>
            </div>
        </div>

        <div class="report-actions">
            <button class="btn secondary" type="button" id="rx-add">＋ Tambah Obat</button>
            <button class="btn" type="submit">💾 Simpan Resep</button>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="panel anim-up" style="--delay:.1s">
    <div class="chart-head">
        <h3>📋 Daftar Resep</h3>
        <form method="GET" action="<?php echo e(url('/resep')); ?>" class="rx-filter">
            <select name="pasien" onchange="this.form.submit()">
                <option value="0">Semua pasien</option>
                <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($p->id); ?>" <?php if($filterPatient === $p->id): echo 'selected'; endif; ?>><?php echo e($p->medical_record_no); ?> · <?php echo e($p->full_name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </form>
    </div>

    <?php $__empty_1 = true; $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <details class="rx-card <?php echo e($rx->status === 'diserahkan' ? 'rx-done' : 'rx-active'); ?>">
            <summary>
                <span class="rx-status <?php echo e($rx->status); ?>"><?php echo e($rx->status === 'aktif' ? '⏳ menunggu' : '✓ diserahkan'); ?></span>
                <b>#<?php echo e($rx->id); ?> · <?php echo e($rx->patient?->full_name); ?></b>
                <small class="muted"><?php echo e($rx->patient?->medical_record_no); ?> · <?php echo e($rx->items->count()); ?> obat · <?php echo e($rx->created_at->locale('id')->translatedFormat('d M Y H:i')); ?></small>
                <span class="rx-doctor">✍ <?php echo e($rx->creator?->full_name ?? '-'); ?><?php echo e($rx->doctor && $rx->doctor->specialty ? ' · '.$rx->doctor->specialty : ''); ?></span>
            </summary>
            <div class="rx-body">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Obat</th><th>Aturan Pakai</th><th>Jumlah</th></tr></thead>
                        <tbody>
                        <?php $__currentLoopData = $rx->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><b><?php echo e($item->medicine?->name); ?></b> <small class="muted"><?php echo e($item->medicine?->unit); ?></small></td>
                                <td><?php echo e($item->dose); ?></td>
                                <td><?php echo e($item->quantity); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php if($rx->notes): ?><p class="muted">📝 <?php echo e($rx->notes); ?></p><?php endif; ?>
                <?php if($canDispense && $rx->status === 'aktif'): ?>
                    <form class="rx-dispense" data-endpoint="<?php echo e(url('/resep/'.$rx->id.'/serahkan')); ?>">
                        <?php echo csrf_field(); ?>
                        <button class="btn" type="submit">✓ Tandai Diserahkan &amp; Kurangi Stok</button>
                        <small class="muted">Stok obat berkurang otomatis sesuai jumlah resep.</small>
                    </form>
                <?php endif; ?>
            </div>
        </details>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <p class="empty">Belum ada resep. <?php if($canWrite): ?>Tulis resep pertama lewat formulir di atas.<?php endif; ?></p>
    <?php endif; ?>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['crumb' => 'resep', 'pageTitle' => 'Resep & Obat'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/resep.blade.php ENDPATH**/ ?>