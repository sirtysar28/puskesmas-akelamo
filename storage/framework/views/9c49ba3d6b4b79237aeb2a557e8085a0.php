<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#065d35">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="robots" content="noindex,nofollow">
    <title>Portal Pasien · Puskesmas Akelamo</title>
    <link rel="icon" type="image/png" href="<?php echo e(asset('assets/img/logo-puskesmas.png')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/app.css')); ?>?v=<?php echo e(filemtime(public_path('assets/app.css'))); ?>">
    <script src="<?php echo e(asset('assets/app.js')); ?>?v=<?php echo e(filemtime(public_path('assets/app.js'))); ?>" defer></script>
</head>
<body class="portal-body">

<header class="landing-nav portal-nav">
    <div class="landing-container landing-nav-inner">
        <a class="landing-brand" href="<?php echo e(route('pasien.home')); ?>">
            <img src="<?php echo e(asset('assets/img/logo-puskesmas.png')); ?>" alt="Logo Puskesmas Akelamo">
            <div>
                <b>Portal Pasien</b>
                <small>PUSKESMAS AKELAMO</small>
            </div>
        </a>
        <nav class="landing-links">
            <a href="<?php echo e(route('pasien.home')); ?>#profil-saya">Profil Saya</a>
            <a href="<?php echo e(route('pasien.home')); ?>#jadwal-dokter">Jadwal Dokter</a>
            <a href="<?php echo e(route('pasien.home')); ?>#daftar-berobat">Daftar Berobat</a>
            <a href="<?php echo e(route('pasien.home')); ?>#riwayat">Riwayat Antrian</a>
        </nav>
        <div class="landing-nav-actions">
            <span class="portal-user"><span class="avatar"><?php echo e(mb_strtoupper(mb_substr(auth()->user()->full_name, 0, 1))); ?></span>
                <b><?php echo e(auth()->user()->full_name); ?></b></span>
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button class="landing-btn ghost" type="submit">Keluar</button>
            </form>
        </div>
    </div>
</header>

<main class="portal-main">
    <div class="landing-container">

        <?php if($errors->any()): ?>
            <div class="form-error-box">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo e($err); ?><br>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="flash-ok"><?php echo e($success); ?></div>
        <?php endif; ?>

        
        <section id="profil-saya">
            <div class="section-head">
                <div>
                    <span class="eyebrow">PORTAL PASIEN</span>
                    <h2>Halo, <?php echo e(auth()->user()->full_name); ?> 👋</h2>
                    <p>Berikut data rekam medis Anda. Tunjukkan nomor RM ini saat datang ke puskesmas.</p>
                </div>
                <span class="tag tag-pulse">Akun Pasien Aktif</span>
            </div>

            <?php if($patient): ?>
                <div class="portal-profile">
                    <div class="rm-box"><?php echo e($patient->medical_record_no); ?></div>
                    <div class="portal-profile-rows">
                        <div><small>Nama Lengkap</small><b><?php echo e($patient->full_name); ?></b></div>
                        <div><small>NIK</small><b><?php echo e($patient->nik ?? '–'); ?></b></div>
                        <div><small>Jenis Kelamin</small><b><?php echo e($patient->gender ?? '–'); ?></b></div>
                        <div><small>Tanggal Lahir</small><b><?php echo e($patient->birth_date?->locale('id')->translatedFormat('d F Y') ?? '–'); ?></b></div>
                        <div><small>No. HP</small><b><?php echo e($patient->phone ?? '–'); ?></b></div>
                        <div><small>Alamat</small><b><?php echo e($patient->address ?? '–'); ?></b></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="panel" style="padding:23px">
                    <p class="muted">Rekam medis Anda belum tertaut ke akun ini. Silakan hubungi petugas
                        pendaftaran puskesmas untuk aktivasi, atau daftar ulang
                        <a href="<?php echo e(url('/pasien')); ?>" style="color:#087944;font-weight:700">di sini</a>.</p>
                </div>
            <?php endif; ?>
        </section>

        
        <section id="daftar-berobat">
            <div class="section-head">
                <div>
                    <span class="eyebrow">PENDAFTARAN</span>
                    <h2>Daftar Berobat Online</h2>
                    <p>Antrian Anda akan dikonfirmasi oleh petugas pendaftaran.</p>
                </div>
            </div>
            <?php if($patient): ?>
                <div class="panel portal-book">
                    <form method="POST" action="<?php echo e(route('pasien.berobat')); ?>">
                        <?php echo csrf_field(); ?>
                        <div class="rx-grid">
                            <div class="field">
                                <label for="visit_date">Tanggal Kunjungan *</label>
                                <input type="date" id="visit_date" name="visit_date" required
                                       min="<?php echo e(now()->toDateString()); ?>" max="<?php echo e(now()->addYear()->toDateString()); ?>"
                                       value="<?php echo e(old('visit_date')); ?>">
                            </div>
                            <div class="field">
                                <label for="doctor_id">Dokter Tujuan (opsional)</label>
                                <select id="doctor_id" name="doctor_id">
                                    <option value="">— Poli Umum —</option>
                                    <?php $__currentLoopData = $doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($d->id); ?>" <?php if(old('doctor_id') == $d->id): echo 'selected'; endif; ?>>
                                            <?php echo e($d->name); ?> · <?php echo e($d->specialty); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                        <div class="field" style="margin-top:14px">
                            <label for="complaint">Keluhan (opsional)</label>
                            <textarea id="complaint" name="complaint" maxlength="500"
                                      placeholder="Contoh: demam 3 hari, pusing, batuk berdahak..."><?php echo e(old('complaint')); ?></textarea>
                        </div>
                        <button class="btn btn-wide" type="submit" style="margin-top:16px">◷ Kirim Pendaftaran Berobat</button>
                    </form>
                </div>
            <?php endif; ?>
        </section>

        
        <section id="riwayat">
            <div class="section-head">
                <div>
                    <span class="eyebrow">RIWAYAT</span>
                    <h2>Antrian &amp; Riwayat Berobat</h2>
                    <p>Status antrian terbaru tampil paling atas.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Tanggal Kunjungan</th><th>Dokter</th><th>Keluhan</th><th>Status</th><th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $appointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($a->visit_date->locale('id')->translatedFormat('d M Y')); ?></td>
                            <td><?php echo e($a->doctor?->name ?? 'Poli Umum'); ?></td>
                            <td><?php echo e($a->complaint ?: '–'); ?></td>
                            <td>
                                <span class="pill <?php echo e(['Batal' => 'red', 'Menunggu' => 'orange', 'Dikonfirmasi' => ''][$a->status] ?? ''); ?>"><?php echo e($a->status); ?></span>
                            </td>
                            <td>
                                <?php if($a->status === 'Menunggu'): ?>
                                    <form method="POST" action="<?php echo e(route('pasien.cancel', $a->id)); ?>"
                                          onsubmit="return confirm('Batalkan antrian ini?')">
                                        <?php echo csrf_field(); ?>
                                        <button class="btn tiny danger" type="submit">Batalkan</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">–</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="empty">Belum ada pendaftaran berobat. Daftarkan diri lewat formulir di atas.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        
        <section id="jadwal-dokter">
            <div class="section-head">
                <div>
                    <span class="eyebrow">REFERENSI</span>
                    <h2>Jadwal Praktik Dokter</h2>
                    <p>Pilih dokter sesuai keluhan Anda pada formulir pendaftaran.</p>
                </div>
            </div>
            <div class="schedule-days">
                <?php $__empty_1 = true; $__currentLoopData = $schedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day => $rows): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="day-card">
                        <div class="day-title"><?php echo e($day); ?></div>
                        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="day-doc">
                                <b><?php echo e($s['doctor']); ?></b>
                                <small><?php echo e($s['specialty']); ?> · <?php echo e($s['time']); ?> · <?php echo e($s['room']); ?></small>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="empty">Jadwal belum tersedia.</p>
                <?php endif; ?>
            </div>
        </section>

        <p class="portal-foot">
            © <?php echo e(date('Y')); ?> Puskesmas Akelamo ·
            <a href="<?php echo e(url('/')); ?>">← Beranda</a> ·
            <a href="<?php echo e(url('/survey')); ?>">Isi Survei Kepuasan</a> ·
            Developed by <a href="https://ciptakaryadigital.id" target="_blank" rel="noopener noreferrer">Cipta Karya Digital</a>
        </p>
    </div>
</main>

</body>
</html>
<?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/akun-pasien.blade.php ENDPATH**/ ?>