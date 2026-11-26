<?php $__env->startSection('content'); ?>
<div class="section-head anim-up">
    <div>
        <h2>Profil Saya</h2>
        <p>Perbarui data akun, email notifikasi, dan ganti sandi secara berkala.</p>
    </div>
    <span class="tag">👤 <?php echo e(strtoupper($user->role)); ?></span>
</div>

<?php if($status): ?>
    <div class="flash-ok"><?php echo e($status); ?></div>
<?php endif; ?>
<?php if(! empty($errors)): ?>
    <div class="form-error-box"><?php echo e(implode(' ', $errors)); ?></div>
<?php endif; ?>

<div class="panels">
    <section class="panel anim-up">
        <h3>📇 Informasi Akun</h3>
        <table class="kv">
            <tr><td>Username</td><td><b><?php echo e($user->username); ?></b></td></tr>
            <tr><td>Nama Lengkap</td><td><?php echo e($user->full_name); ?></td></tr>
            <tr><td>Peran</td><td><span class="tag"><?php echo e($user->role); ?></span></td></tr>
            <tr><td>Email Notifikasi</td><td><?php if($user->email): ?><b><?php echo e($user->email); ?></b><?php else: ?><span class="muted">belum diisi — tanpa email, notifikasi hanya tampil di aplikasi</span><?php endif; ?></td></tr>
            <tr><td>Terdaftar</td><td><?php echo e($user->created_at->locale('id')->translatedFormat('d F Y')); ?></td></tr>
        </table>

        <form method="POST" action="<?php echo e(url('/profil')); ?>" class="panel-form">
            <?php echo csrf_field(); ?>
            <h3>✎ Perbarui Profil</h3>
            <div class="field">
                <label for="full_name">Nama Lengkap</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo e(old('full_name', $user->full_name)); ?>" required maxlength="150">
            </div>
            <div class="field">
                <label for="email">Email Notifikasi (opsional)</label>
                <input type="email" id="email" name="email" value="<?php echo e(old('email', $user->email)); ?>" maxlength="150" placeholder="nama@email.com">
                <small>Email dipakai mengirim notifikasi transaksi &amp; aktivitas bila SMTP diaktifkan admin.</small>
            </div>
            <button class="btn" type="submit">Simpan Profil</button>
        </form>
    </section>

    <section class="panel anim-up" style="--delay:.12s">
        <h3>🔐 Ganti Sandi</h3>
        <p class="muted">Minimal 10 karakter, mengandung huruf &amp; angka. Sandi lama wajib untuk konfirmasi.</p>
        <form method="POST" action="<?php echo e(url('/profil/sandi')); ?>" class="panel-form">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="current_password">Sandi Lama</label>
                <input type="password" id="current_password" name="current_password" required maxlength="200" autocomplete="current-password">
            </div>
            <div class="field">
                <label for="password">Sandi Baru</label>
                <input type="password" id="password" name="password" required minlength="10" maxlength="200" autocomplete="new-password">
            </div>
            <div class="field">
                <label for="password_confirmation">Ulangi Sandi Baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required maxlength="200" autocomplete="new-password">
            </div>
            <button class="btn" type="submit">Ganti Sandi</button>
        </form>

        <h3 style="margin-top:24px">🕘 Aktivitas Saya Terakhir</h3>
        <?php $__empty_1 = true; $__currentLoopData = $recent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="schedule-row">
                <b><?php echo e($log->action); ?></b>
                <div class="muted"><?php echo e($log->entity); ?><?php echo e($log->record_id ? ' #'.$log->record_id : ''); ?> · <?php echo e($log->created_at->locale('id')->translatedFormat('d M Y H:i')); ?></div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="muted">Belum ada aktivitas tercatat.</p>
        <?php endif; ?>
    </section>
</div>

<p class="footer-note">🔒 Sandi disimpan sebagai hash bcrypt (cost 12) — tidak ada yang dapat membaca sandi Anda, termasuk administrator.</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['crumb' => 'profil', 'pageTitle' => 'Profil Saya'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/profil.blade.php ENDPATH**/ ?>