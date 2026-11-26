<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#076e3b">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="robots" content="noindex,nofollow">
    <title>Survei Kepuasan | Puskesmas Akelamo</title>
    <link rel="icon" type="image/png" href="<?php echo e(asset('assets/img/logo-puskesmas.png')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/app.css')); ?>?v=<?php echo e(filemtime(public_path('assets/app.css'))); ?>">
    <script src="<?php echo e(asset('assets/app.js')); ?>?v=<?php echo e(filemtime(public_path('assets/app.js'))); ?>" defer></script>
</head>
<body class="survey-body">
<div class="survey-shell">
    <div class="survey-box anim-up">
        <div class="survey-brand">
            <img src="<?php echo e(asset('assets/img/logo-puskesmas.png')); ?>" alt="Logo Puskesmas Akelamo">
            <div>
                <strong>Puskesmas Akelamo</strong>
                <small>Survei Kepuasan Masyarakat</small>
            </div>
        </div>

        <div id="survey-area">
            <h1>Bagaimana layanan kami?</h1>
            <p>Penilaian Anda membantu kami meningkatkan kualitas pelayanan kesehatan. Survei ini anonim dan tidak meminta data pribadi apa pun.</p>

            <form id="survey-form" method="POST" action="<?php echo e(route('survey.submit')); ?>">
                <?php echo csrf_field(); ?>
                <div class="field">
                    <label for="service">Jenis Layanan</label>
                    <select id="service" name="service" required>
                        <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($service); ?>"><?php echo e($service); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="field">
                    <label>Nilai Pelayanan</label>
                    <div class="rating-choice">
                        <?php $__currentLoopData = range(1, 5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label>
                                <input type="radio" name="rating" value="<?php echo e($value); ?>" <?php if($value === 5): echo 'checked'; endif; ?>>
                                <span><?php echo e(['😕','😐','🙂','😃','🤩'][$value - 1]); ?></span>
                                <small><?php echo e(['Kurang','Cukup','Baik','Sangat Baik','Luar Biasa'][$value - 1]); ?></small>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <div class="field">
                    <label for="suggestion">Kritik &amp; Saran (opsional)</label>
                    <textarea id="suggestion" name="suggestion" maxlength="1000"
                              placeholder="Tulis masukan Anda di sini (jangan memuat data medis)..."></textarea>
                </div>

                
                <div class="hp-field" aria-hidden="true">
                    <label for="website">Website</label>
                    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <div class="form-error" id="feedback" role="alert"></div>

                <button class="btn btn-survey" type="submit">Kirim Penilaian ✓</button>
            </form>
        </div>

        <footer class="survey-footer">
            © <?php echo e(date('Y')); ?> Puskesmas Akelamo · Developed by
            <a href="https://ciptakaryadigital.id" target="_blank" rel="noopener noreferrer">Cipta Karya Digital</a>
        </footer>
    </div>
</div>
</body>
</html>
<?php /**PATH /Users/permatabank/Desktop/tysar/a Project/CV CIPTA KARYA DIGITAL INDONESIA/APLIKASI MANAJEMEN PUSKESMAS AKELAMO/puskesmas-akelamo/resources/views/survey-public.blade.php ENDPATH**/ ?>