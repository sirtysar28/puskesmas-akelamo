<?php $__env->startSection('content'); ?>
<div class="section-head anim-up">
    <div>
        <h2>Pengguna &amp; Hak Akses</h2>
        <p>Admin dapat membuat akun untuk Dokter, Petugas, dan Keuangan.</p>
    </div>
    <button class="btn" id="add-user" type="button">＋ Akun Baru</button>
</div>

<div class="table-wrap anim-up" style="--delay:.12s"
     id="users-table"
     data-rows="<?php echo e(json_encode($users->map(fn ($u) => [
         'id' => $u->id,
         'full_name' => $u->full_name,
         'username' => $u->username,
         'role' => $u->role,
         'is_active' => $u->is_active,
     ]))); ?>">
    <table class="data-table">
        <thead>
        <tr><th>Nama</th><th>Username</th><th>Hak Akses</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($u->full_name); ?></td>
                <td><?php echo e($u->username); ?></td>
                <td><span class="pill"><?php echo e($u->role); ?></span></td>
                <td><span class="pill <?php echo e($u->is_active ? '' : 'red'); ?>"><?php echo e($u->is_active ? 'Aktif' : 'Nonaktif'); ?></span></td>
                <td>
                    <button class="btn tiny secondary" data-reset="<?php echo e($u->id); ?>" type="button">Reset Sandi</button>
                    <?php if($u->id !== auth()->id()): ?>
                        <button class="btn tiny <?php echo e($u->is_active ? 'danger' : 'secondary'); ?>" data-active="<?php echo e($u->id); ?>"
                                data-status="<?php echo e($u->is_active ? '1' : '0'); ?>" type="button">
                            <?php echo e($u->is_active ? 'Nonaktifkan' : 'Aktifkan'); ?>

                        </button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>

<p class="footer-note">Untuk keamanan, penghapusan pengguna tidak tersedia. Akun dapat dinonaktifkan dan seluruh sesi aktifnya akan diputus.</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['crumb' => 'users', 'pageTitle' => 'Pengguna & Akses'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/users.blade.php ENDPATH**/ ?>