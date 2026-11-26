<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#076e3b">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="robots" content="noindex,nofollow">
    <title>Layanan Pasien | Puskesmas Akelamo</title>
    <link rel="icon" type="image/png" href="<?php echo e(asset('assets/img/logo-puskesmas.png')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/app.css')); ?>?v=<?php echo e(filemtime(public_path('assets/app.css'))); ?>">
    <script src="<?php echo e(asset('assets/app.js')); ?>?v=<?php echo e(filemtime(public_path('assets/app.js'))); ?>" defer></script>
</head>
<body class="survey-body">
<div class="survey-shell survey-shell-wide">
    <div class="survey-box anim-up">
        <div class="survey-brand">
            <img src="<?php echo e(asset('assets/img/logo-puskesmas.png')); ?>" alt="Logo Puskesmas Akelamo">
            <div>
                <strong>Puskesmas Akelamo</strong>
                <small>Layanan Pasien Online</small>
            </div>
        </div>

        <?php if($errors->any()): ?>
            <div class="form-error-box">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php echo e($err); ?><br>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="regis-success">
                <div class="regis-check">✓</div>
                <h2>Registrasi Berhasil!</h2>
                <p>Terima kasih, <b><?php echo e($successName); ?></b>. Nomor rekam medis Anda:</p>
                <div class="rm-box"><?php echo e($success); ?></div>
                <p>Gunakan data berikut untuk <b>login &amp; daftar berobat online</b>:</p>
                <div class="rm-box" style="font-size:15px">👤 Username: <b><?php echo e($successUsername); ?></b></div>
                <p class="muted">Kata sandi = yang Anda buat di atas. Simpan nomor RM ini &amp; tunjukkan di loket saat berobat. Data Anda terlindungi dan terenkripsi.</p>
                <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
                    <a class="btn" href="<?php echo e(url('/login')); ?>">→ Login &amp; Daftar Berobat</a>
                    <a class="btn secondary" href="<?php echo e(url('/pasien')); ?>">Daftarkan Pasien Lain</a>
                </div>
            </div>
        <?php else: ?>
            <div id="pt-area">
                <h1>Jadwal Praktik Dokter</h1>
                <p>Pilih jadwal dokter sesuai kebutuhan, lalu daftarkan diri Anda sebagai pasien — nomor rekam medis dibuat otomatis oleh sistem.</p>

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

                <h1 style="margin-top:30px">Registrasi Pasien Baru</h1>
                <p>Isi data dengan benar. Nomor <b>Rekam Medis (RM)</b> dibuat otomatis — sekaligus menjadi <b>username</b> akun pasien Anda untuk login &amp; mendaftar berobat online.</p>

                <form method="POST" action="<?php echo e(url('/pasien/daftar')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="rx-grid">
                        <div class="field">
                            <label for="full_name">Nama Lengkap *</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo e(old('full_name')); ?>" required maxlength="160" placeholder="Nama sesuai KTP">
                        </div>
                        <div class="field">
                            <label for="nik">NIK (16 digit)</label>
                            <input type="text" id="nik" name="nik" value="<?php echo e(old('nik')); ?>" maxlength="16" pattern="\d{16}" placeholder="16 digit angka (opsional)">
                        </div>
                    </div>
                    <div class="rx-grid">
                        <div class="field">
                            <label for="gender">Jenis Kelamin *</label>
                            <select id="gender" name="gender" required>
                                <option value="Laki-laki" <?php if(old('gender') === 'Laki-laki'): echo 'selected'; endif; ?>>Laki-laki</option>
                                <option value="Perempuan" <?php if(old('gender') === 'Perempuan'): echo 'selected'; endif; ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="birth_date">Tanggal Lahir *</label>
                            <input type="date" id="birth_date" name="birth_date" value="<?php echo e(old('birth_date')); ?>" required max="<?php echo e(now()->subDay()->toDateString()); ?>">
                        </div>
                    </div>
                    <div class="rx-grid">
                        <div class="field">
                            <label for="phone">No. HP</label>
                            <input type="text" id="phone" name="phone" value="<?php echo e(old('phone')); ?>" maxlength="30" placeholder="08xx-xxxx-xxxx (opsional)">
                        </div>
                        <div class="field">
                            <label for="address">Alamat</label>
                            <input type="text" id="address" name="address" value="<?php echo e(old('address')); ?>" maxlength="500" placeholder="Alamat domisili (opsional)">
                        </div>
                    </div>
                    <div class="rx-grid">
                        <div class="field">
                            <label for="password">Kata Sandi Akun * (min. 8 karakter)</label>
                            <input type="password" id="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Buat kata sandi akun pasien">
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Ulangi Kata Sandi *</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Ketik ulang kata sandi">
                        </div>
                    </div>
                    <div class="field">
                        <label for="captcha">Kode Keamanan *</label>
                        <div class="captcha-row">
                            <img id="captcha-img" src="<?php echo e(url('/captcha')); ?>" alt="Kode captcha" width="150" height="46">
                            <button class="captcha-refresh" type="button" id="captcha-refresh" title="Ganti kode">↻</button>
                            <input type="text" id="captcha" name="captcha" required maxlength="8" autocomplete="off" placeholder="5 karakter">
                        </div>
                    </div>
                    <button class="btn btn-wide" type="submit">✓ Daftar &amp; Buat Akun Pasien</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="survey-foot">
            <a href="<?php echo e(url('/')); ?>">← Beranda</a> ·
            <a href="<?php echo e(url('/login')); ?>">Login Pasien / Staf</a> ·
            <a href="<?php echo e(url('/survey')); ?>">Isi Survei Kepuasan</a>
        </div>
    </div>
    <div class="survey-credit">© <?php echo e(date('Y')); ?> Puskesmas Akelamo · Developed by <a href="https://ciptakaryadigital.id" target="_blank" rel="noopener noreferrer">Cipta Karya Digital</a></div>
</div>
</body>
</html>
<?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/pasien-publik.blade.php ENDPATH**/ ?>